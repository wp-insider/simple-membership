import { string, object, literal, ZodType } from "zod";

/**
 * Defining variables that would come from PHP.
 */
// @ts-ignore
const formID = typeof form_id !== "undefined" ? form_id : "swpm-profile-form";
// @ts-ignore
const isStrongPasswordEnabled =
    typeof strong_password_enabled !== "undefined"
        ? strong_password_enabled
        : false;

document.addEventListener("DOMContentLoaded", function () {
    // The current email address of the user. This is utilized during email field validation.
    let existingEmailValue: string | null = null;

    // Field options configuration object.
    const formConfig = {
        email: {
            value: "" as string | null,
            eventListener: ["blur"],
            active: true as boolean,
            isAsyncValidation: true as boolean,
            isDirty: false,
            rule: string({
                required_error: validationMsg?.email?.required,
                invalid_type_error: validationMsg?.email?.invalid,
            })
                .trim()
                .min(1, { message: validationMsg?.email?.required })
                .email({ message: validationMsg?.email?.invalid })
                .refine(
                    async function (value) {
                        const parseResult = string().email().safeParse(value);
                        if (parseResult.success) {
                            if (value === existingEmailValue) {
                                // The entered email is the current email of the member. So no need to check existing email records.
                                return true;
                            }
                            const isAvailable = await checkAvailability(
                                value,
                                "email"
                            );
                            return isAvailable;
                        }
                        return true;
                    },
                    {
                        message: validationMsg?.email?.exists,
                    }
                ),
        },
        password: {
            value: "" as string | null,
            eventListener: ["blur", "input"],
            active: true as boolean,
            isAsyncValidation: false as boolean,
            isDirty: false,
            rule: isStrongPasswordEnabled
                ? string({
                      required_error: validationMsg?.password?.required,
                      invalid_type_error: validationMsg?.password?.invalid,
                  })
                      .regex(/^(?=.*\d)(?=.*[A-Z])(?=.*[a-z]).+$/, {
                          message: validationMsg?.password?.regex,
                      })
                      .min(8, { message: validationMsg?.password?.minLength })
                      .optional().or(literal(''))
                : string({
                      required_error: validationMsg?.password?.required,
                      invalid_type_error: validationMsg?.password?.invalid,
                  }).optional().or(literal('')),
        },
        repass: {
            value: "" as string | null,
            eventListener: ["blur", "input"],
            active: true as boolean,
            isAsyncValidation: false as boolean,
            isDirty: false,
            rule: string({
                required_error: validationMsg?.repass?.required,
                invalid_type_error: validationMsg?.repass?.invalid,
            }).refine(
                function (value) {
                    return value === this.password.value;
                },
                {
                    message: validationMsg?.repass?.mismatch,
                }
            ),
        },
    };

    type FormValidatorsType = {
        [key: string]: ZodType<any>;
    };

    const FormValidators: FormValidatorsType = {
        email: formConfig.email.rule,
        password: formConfig.password.rule,
        repass: formConfig.repass.rule,
    };

    const FormSchema = object(FormValidators);

    const profileForm = document.getElementById(formID) as HTMLFormElement;

    // Grabs the saved email address of the member.
    const emailField = profileForm.querySelector(
        `.swpm-profile-form-email`
    ) as HTMLInputElement;
    if (emailField) {
        existingEmailValue = emailField.value;
        formConfig.email.value = existingEmailValue;
    }

    const fields: string[] = Object.keys(FormValidators);

    /**
     * Add event listeners to all the active fields.
     * A field could have multiple event listeners.
     */
    fields.forEach((field) => {
        const fieldOption = formConfig[field as keyof typeof formConfig];
        if (fieldOption.active) {
            fieldOption.eventListener.forEach((eventListener) => {
                profileForm
                    ?.querySelector(`.swpm-profile-form-${field}`)
                    ?.addEventListener(eventListener, (e) => {
                        handleDomEvent(e, field);
                    });
            });
        }
    });

    /**
     * The 'retype-password' field needs a special treatment.
     * If user fills up the both the password and retype password correctly, and then if he/she changes
     * the 'password' field, the "retype-password" won't show "Password didn't matched" error message
     * until the user interacts with the 'retype-password' field or clicks the submit button. So to 
     * prevent this and to enhance user experience, we also need to validate the 'retype-password' field
     * whenever the 'password' is changed. Also check if the retype-password is dirty, to prevent
     * doing validation initially.
     */
    profileForm
        ?.querySelector(`.swpm-profile-form-password`)
        ?.addEventListener("input", () => {
            if (formConfig.repass.isDirty) {   
                validateInput("repass", formConfig.repass.value);
            }
        });

    /**
     * Listen to form submit.
     */
    profileForm?.addEventListener("submit", async function (e) {
        e.preventDefault();

        // The variable holds the overall validation status.
        let validationSucess = true;

        for (const key in formConfig) {
            // Checks it the current field of iteration is not active.
            // If so, then skip the validation check for this field as its not active.
            if (!formConfig[key as keyof typeof formConfig].active) {
                continue;
            }

            let isSuccess = await validateInput(
                key,
                formConfig[key as keyof typeof formConfig].value
            );

            // Checks if the current field of iteration has any error.
            if (!isSuccess) {
                validationSucess = false;
            }
        }

        // Checks if all validations are successful.
        if (validationSucess) {
            // Submits the form.
            profileForm.submit();
        } else {
            // Scroll to first error field into view.
            scrollToFirstErrorField();
        }
    });

    /**
     * Validates the form input field by the input value.
     *
     * @param field string The field name
     * @param value any Input value
     * @returns void
     */
    async function validateInput(field: string, value: any) {
        let isValidationSuccessful = false;

        // To ensure that the field was touched.
        formConfig[field as keyof typeof formConfig].isDirty = true;

        const fieldToValidate = FormSchema.pick({ [field]: true });

        let parseResult;
        // Check whether the validation involves asynchronous validation form server.
        if (formConfig[field as keyof typeof formConfig].isAsyncValidation) {
            parseResult = await fieldToValidate.safeParseAsync({
                [field]: value,
            });
        } else {
            parseResult = fieldToValidate.safeParse({
                [field]: value,
            });
        }

        const targetRow = getRowByField(field);
        const targetFieldDesc = getDescByField(field);

        if (!parseResult.success) {
            targetRow?.classList.add("error");
            const issues = parseResult.error.issues;

            // Checks if the target input has a description field. Checkboxes don't have a description field.
            if (targetFieldDesc) {
                // Clear the description field.
                targetFieldDesc.innerHTML = "";
                const errorLists = document.createElement("ul");
                for (const i in issues) {
                    const error = issues[i];
                    // Add the error message on the target field.
                    const errorMsg: string = error.message;
                    const errorItem = document.createElement("li");
                    errorItem.innerText = errorMsg;
                    errorLists.appendChild(errorItem);

                    // Check if its a 'required' error. If so, only show the 'required' error.
                    if (error.code === "too_small" && error.minimum === 1) {
                        /* 
                            The validation rule for checking 'required' error, is to check to see if the value is
                            minimum of 1 character. The error object includes properties (code: "too_small", minimum: 1)
                            which is helpful for the check. Also the 'required' validation is executed first, so by 
                            breaking the loop there will no other error messages other than only the "<field> is 
                            required" message. 
                        */
                        break;
                    }
                }
                targetFieldDesc.appendChild(errorLists);
            }
        } else {
            // Remove the error message.
            if (targetFieldDesc) {
                targetFieldDesc.innerHTML = "";
            }

            targetRow?.classList.remove("error");
            isValidationSuccessful = true;
        }

        return isValidationSuccessful;
    }

    /**
     * Scroll into view to the first input field that is in error state.
     */
    function scrollToFirstErrorField() {
        const profileForm = document.getElementById(formID);
        const firstErrorSection = profileForm?.querySelector(
            ".swpm-profile-form-row.error"
        ) as HTMLElement;
        if (firstErrorSection) {
            firstErrorSection.scrollIntoView({
                behavior: "smooth",
                block: "start",
            });

            const firstErrorField = firstErrorSection.querySelector(
                ".swpm-profile-form-field"
            ) as HTMLInputElement;
            firstErrorField.focus();
        }
    }

    /**
     * Handles form inputs interactions.
     *
     * @param e Event
     * @param field string Field name.
     */
    function handleDomEvent(e: any, field: string) {
        const target = e.target as HTMLInputElement; // Type assertion

        /**
         * For checkbox inputs, the value is fixed unlike text type inputs.
         * So to detect whether a checkbox is checked or not is to check its 'checked' status.
         */
        const inputValue = target.type === "checkbox" ? target.checked : target.value;

        formConfig[field as keyof typeof formConfig].value = inputValue;

        // Validates the input value
        validateInput(field, inputValue);
    }

    /**
     * Asynchronously checks whether the field value exists on the database.
     *
     * @param value The value to retrieve
     * @param field The field to retrieve the value of.
     * @returns boolean
     */
    async function checkAvailability(value: string, field: string) {
        // @ts-ignore
        const queryArgs = swpmFormValidationAjax.query_args;
        // @ts-ignore
        const ajaxURL = swpmFormValidationAjax.ajax_url;

        // Choosing the ajax action.
        if (field === "email") {
            queryArgs.action = "swpm_validate_email";
        }

        queryArgs.fieldValue = value;
        const queryString = new URLSearchParams(queryArgs).toString();
        const apiUrl = ajaxURL + "?" + queryString;

        return new Promise((resolve) => {
            fetch(apiUrl)
                .then((response) => {
                    // Check if the response status is OK (status code 200)
                    if (response.ok) {
                        // Parse the response body as JSON
                        return response.json();
                    } else {
                        // Handle the error if the response status is not OK
                        throw new Error("Request failed");
                    }
                })
                .then((data) => {
                    // the response body contains an array with the target value as boolean at index 1.
                    const isAvailable = data[1];

                    resolve(isAvailable);
                })
                .catch((error) => {
                    // Handle any errors that occurred during the fetch
                    console.error("Error: ", error);
                });
        });
    }

    /**
     * Returns the target description field.
     *
     * @param field string The field name
     * @returns HTMLDivElement
     */
    function getDescByField(field: string) {
        const profileForm = document.getElementById(formID);

        const targetField = profileForm?.querySelector(
            `.swpm-profile-${field}-row`
        );

        return targetField?.querySelector(`.swpm-profile-form-desc`);
    }

    /**
     * Returns the target field's row.
     *
     * @param field string The field name
     * @returns HTMLDivElement
     */
    function getRowByField(field: string) {
        const profileForm = document.getElementById(formID);
        return profileForm?.querySelector(`.swpm-profile-${field}-row`);
    }
});

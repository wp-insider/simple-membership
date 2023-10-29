import { string, object, literal, ZodType } from "zod";

/**
 * Defining variables that would come from PHP.
 */
// @ts-ignore
const formID = typeof form_id !== "undefined" ? form_id : "swpm-registration-form";
// @ts-ignore
const isTermsEnabled = typeof terms_enabled !== "undefined" ? terms_enabled : false;
// @ts-ignore
const isPPEnabled = typeof pp_enabled !== "undefined" ? pp_enabled : false;
// @ts-ignore
const isStrongPasswordEnabled = typeof strong_password_enabled !== "undefined" ? strong_password_enabled : false;

document.addEventListener("DOMContentLoaded", function () {
    // Field options configuration object.
    const formConfig = {
        username: {
            value: "" as string | null,
            eventListener: ["blur"],
            active: true as boolean,
            isAsyncValidation: true as boolean,
            rule: string({
                required_error: validationMsg?.username?.required,
                invalid_type_error: validationMsg?.username?.invalid,
            })
                .trim()
                .min(1, { message: validationMsg?.username?.required })
                .regex(/^(?=[a-zA-Z0-9.\-_*@]+$)/, {
                    message: validationMsg?.username?.regex,
                })
                .min(4, { message: validationMsg?.username?.minLength })
                .refine(
                    async function (value) {
                        const usernameSchema = string().regex(
                            /^(?=[a-zA-Z0-9.\-_*@]+$)/
                        );
                        const parseResult = usernameSchema.safeParse(value);
                        if (parseResult.success) {
                            const isAvailable = await checkAvailability(
                                value,
                                "username"
                            );
                            return isAvailable;
                        }
                        return true;
                    },
                    {
                        message: validationMsg?.username?.exists,
                    }
                ),
        },
        email: {
            value: "" as string | null,
            eventListener: ["blur"],
            active: true as boolean,
            isAsyncValidation: true as boolean,
            rule: string({
                required_error: validationMsg?.email?.required,
                invalid_type_error: validationMsg?.email?.invalid,
            })
                .trim()
                .min(1, { message: validationMsg?.email?.required })
                .email({ message: validationMsg?.email?.invalid })
                .refine(
                    async function (value) {
                        const emailSchema = string().email();
                        const parseResult = emailSchema.safeParse(value);
                        if (parseResult.success) {
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
            rule: isStrongPasswordEnabled
                ? string({
                      required_error: validationMsg?.password?.required,
                      invalid_type_error: validationMsg?.password?.invalid,
                  })
                      .min(1, { message: validationMsg?.password?.required })
                      .regex(/^(?=.*\d)(?=.*[A-Z])(?=.*[a-z]).+$/, {
                          message: validationMsg?.password?.regex,
                      })
                      .min(8, { message: validationMsg?.password?.minLength })
                : string({
                      required_error: validationMsg?.password?.required,
                      invalid_type_error: validationMsg?.password?.invalid,
                  }).min(1, { message: validationMsg?.password?.required }),
        },
        repass: {
            value: "" as string | null,
            eventListener: ["blur", "input"],
            active: true as boolean,
            isAsyncValidation: false as boolean,
            rule: string({
                required_error: validationMsg?.repass?.required,
                invalid_type_error: validationMsg?.repass?.invalid,
            })
                .min(1, { message: validationMsg?.repass?.required })
                .refine(
                    function (value) {
                        return value === this.password.value;
                    },
                    {
                        message: validationMsg?.repass?.mismatch,
                    }
                ),
        },
        firstname: {
            value: "" as string | null,
            eventListener: ["input"],
            active: true as boolean,
            isAsyncValidation: false as boolean,
            rule: string({
                required_error: validationMsg?.firstname?.required,
                invalid_type_error: validationMsg?.firstname?.invalid,
            })
                .trim()
                .optional(),
        },
        lastname: {
            value: "" as string | null,
            eventListener: ["input"],
            active: true as boolean,
            isAsyncValidation: false as boolean,
            rule: string({
                required_error: validationMsg?.lastname?.required,
                invalid_type_error: validationMsg?.lastname?.invalid,
            })
                .trim()
                .optional(),
        },
        terms: {
            value: false as boolean,
            eventListener: ["change"],
            active: isTermsEnabled as boolean,
            isAsyncValidation: false as boolean,
            rule: literal(true, {
                errorMap: () => ({
                    message: validationMsg?.terms?.required,
                }),
            }),
        },
        pp: {
            value: false as boolean,
            eventListener: ["change"],
            active: isPPEnabled as boolean,
            isAsyncValidation: false as boolean,
            rule: literal(true, {
                errorMap: () => ({
                    message: validationMsg?.pp?.required,
                }),
            }),
        },
    };

    type RegistrationValidators = {
        username: typeof formConfig.username.rule;
        email: typeof formConfig.email.rule;
        password: typeof formConfig.password.rule;
        repass: typeof formConfig.repass.rule;
        firstname: typeof formConfig.firstname.rule;
        lastname: typeof formConfig.lastname.rule;
        [key: string]: ZodType<any>;
    };

    const RegistrationValidators: RegistrationValidators = {
        username: formConfig.username.rule,
        email: formConfig.email.rule,
        password: formConfig.password.rule,
        repass: formConfig.repass.rule,
        firstname: formConfig.firstname.rule,
        lastname: formConfig.lastname.rule,
    };

    if (isTermsEnabled) {
        RegistrationValidators["terms"] = formConfig.terms.rule;
    }

    if (isPPEnabled) {
        RegistrationValidators["pp"] = formConfig.pp.rule;
    }

    const RegistrationFormSchema = object(RegistrationValidators);

    const registrationForm = document.getElementById(formID) as HTMLFormElement;

    const fields: string[] = Object.keys(RegistrationValidators);

    /**
     * Add event listeners to all the active fields.
     * A field could have multiple event listeners.
     */
    fields.forEach((field) => {
        const fieldOption = formConfig[field as keyof typeof formConfig];
        if (fieldOption.active) {
            fieldOption.eventListener.forEach((eventListener) => {
                registrationForm
                    ?.querySelector(`.swpm-registration-form-${field}`)
                    ?.addEventListener(eventListener, (e) => {
                        handleDomEvent(e, field);
                    });
            });
        }
    });

    /**
     * Listen to form submit.
     */
    registrationForm?.addEventListener("submit", async function (e) {
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
            registrationForm.submit();
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

        const fieldToValidate = RegistrationFormSchema.pick({ [field]: true });

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
        const registrationForm = document.getElementById(formID);
        const firstErrorSection = registrationForm?.querySelector(
            ".swpm-registration-form-row.error"
        ) as HTMLElement;
        if (firstErrorSection) {
            firstErrorSection.scrollIntoView({
                behavior: "smooth",
                block: "start",
            });

            // smoothScrollToElement(firstErrorSection) // TODO: Need work on this later.

            const firstErrorField = firstErrorSection.querySelector(
                ".swpm-registration-form-field"
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
        const inputValue =
            target.type === "checkbox" ? target.checked : target.value;

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
        // // Checks whether the input field is empty or not. If empty, then return false in order to show no validation error.
        // if (value.trim() === "") {
        //     return true;
        // }

        // @ts-ignore
        const queryArgs = swpmRegFormAjax.query_args;
        // @ts-ignore
        const ajaxURL = swpmRegFormAjax.ajax_url;

        // choosing the ajax action.
        if (field === "username") {
            queryArgs.action = "swpm_validate_user_name";
        } else {
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
        const registrationForm = document.getElementById(formID);

        const targetField = registrationForm?.querySelector(
            `.swpm-registration-${field}-row`
        );

        return targetField?.querySelector(`.swpm-registration-form-desc`);
    }

    /**
     * Returns the target field's row.
     *
     * @param field string The field name
     * @returns HTMLDivElement
     */
    function getRowByField(field: string) {
        const registrationForm = document.getElementById(formID);
        return registrationForm?.querySelector(
            `.swpm-registration-${field}-row`
        );
    }

    /**
     * Scroll to the target DOM element.
     *
     * @param element HTMLElement The element to scroll to.
     */
    const smoothScrollToElement = (element: HTMLElement) => {
        const elementPosition = element.getBoundingClientRect().top;
        const startingY = window.pageYOffset;
        const targetY = startingY + elementPosition;

        const totalScrollDistance = targetY - startingY;
        let currentScrollPosition = startingY;

        const easeInOutQuad = (t, b, c, d) => {
            // Function for smooth scroll animation
            t /= d / 2;
            if (t < 1) return (c / 2) * t * t + b;
            t--;
            return (-c / 2) * (t * (t - 2) - 1) + b;
        };

        const animationStartTime = performance.now();

        const scroll = (timestamp) => {
            const timeElapsed = timestamp - animationStartTime;
            window.scrollTo(
                0,
                easeInOutQuad(timeElapsed, startingY, totalScrollDistance, 400)
            ); // Adjust 1000 to control the scroll duration

            if (timeElapsed < 400) {
                requestAnimationFrame(scroll);
            } else {
                window.scrollTo(0, targetY);
            }
        };

        requestAnimationFrame(scroll);
    };
});

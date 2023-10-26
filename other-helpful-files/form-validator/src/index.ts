import { string, object, literal, ZodType } from "zod";

/**
 * Defining variables that would come from PHP.
 */
// @ts-ignore
const formID =
    typeof form_id !== "undefined" ? form_id : "swpm-registration-form";
// @ts-ignore
const isTermsEnabled =
    typeof terms_enabled !== "undefined" ? terms_enabled : false;
// @ts-ignore
const isPPEnabled = typeof pp_enabled !== "undefined" ? pp_enabled : false;
// @ts-ignore
const isStrongPasswordEnabled =
    typeof strong_password_enabled !== "undefined"
        ? strong_password_enabled
        : false;
// @ts-ignore
const isCaptchaEnabled = false;

// const validationMsg = {
//     username: {
//         required: "Username is required",
//         invalid: "Invalid username",
//         regex: "Usernames can only contain: letters, numbers and .-_*@",
//         minLength: "Minimum 4 characters required",
//         exists: "Username already exists",
//     },
//     email: {
//         required: "Email is required",
//         invalid: "Invalid email",
//         exists: "Email already exists",
//     },
//     password: {
//         required: "Password is required",
//         invalid: "Invalid password",
//         regex: "Must contain a digit, an uppercase and a lowercase letter",
//         minLength: "Minimum 8 characters required",
//     },
//     repass: {
//         required: "Retype password is required",
//         invalid: "Invalid password",
//         mismatch: "Password don't match",
//         minLength: "Minimum 8 characters required",
//     },
//     firstname: {
//         required: "First name is required",
//         invalid: "Invalid name",
//     },
//     lastname: {
//         required: "Last name is required",
//         invalid: "Invalid name",
//     },
//     terms: {
//         required: "You must accept the terms & conditions",
//     },
//     pp: {
//         required: "You must accept the privacy policy",
//     },
// };

document.addEventListener("DOMContentLoaded", function () {
    // Field options configuration object.
    const formData = {
        username: {
            value: "" as string | null,
            eventListener: "blur",
            active: true as boolean,
            isAsyncValidation: true as boolean,
            rule: string({
                required_error: validationMsg?.username?.required,
                invalid_type_error: validationMsg?.username?.invalid,
            })
                .regex(/^(?=[a-zA-Z0-9.\-_*@]+$)/, {
                    message: validationMsg?.username?.regex,
                })
                .min(4, { message: validationMsg?.username?.minLength })
                .min(1, { message: validationMsg?.username?.required })
                .refine(
                    async function (value) {
                        const isAvailable = await checkAvailability(
                            value,
                            "username"
                        );
                        return isAvailable;
                    },
                    {
                        message: validationMsg?.username?.exists,
                    }
                ),
        },
        email: {
            value: "" as string | null,
            eventListener: "blur",
            active: true as boolean,
            isAsyncValidation: true as boolean,
            rule: string({
                required_error: validationMsg?.email?.required,
                invalid_type_error: validationMsg?.email?.invalid,
            })
                .email({ message: validationMsg?.email?.invalid })
                .min(1, { message: validationMsg?.email?.required })
                .refine(
                    async function (value) {
                        const isAvailable = await checkAvailability(value, "email");
                        return isAvailable;
                    },
                    {
                        message: validationMsg?.email?.exists,
                    }
                ),
        },
        password: {
            value: "" as string | null,
            eventListener: "keyup",
            active: true as boolean,
            isAsyncValidation: false as boolean,
            rule: isStrongPasswordEnabled
                ? string({
                      required_error: validationMsg?.password?.required,
                      invalid_type_error: validationMsg?.password?.invalid,
                  })
                      .regex(/^(?=.*\d)(?=.*[A-Z])(?=.*[a-z]).+$/, {
                          message: validationMsg?.password?.regex,
                      })
                      .min(8, { message: validationMsg?.password?.minLength })
                      .min(1, { message: validationMsg?.password?.required })
                : string({
                      required_error: validationMsg?.password?.required,
                      invalid_type_error: validationMsg?.password?.invalid,
                  }).min(1, { message: validationMsg?.password?.required }),
        },
        repass: {
            value: "" as string | null,
            eventListener: "keyup",
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
            eventListener: "keyup",
            active: true as boolean,
            isAsyncValidation: false as boolean,
            rule: string({
                required_error: validationMsg?.firstname?.required,
                invalid_type_error: validationMsg?.firstname?.invalid,
            }).optional(),
        },
        lastname: {
            value: "" as string | null,
            eventListener: "keyup",
            active: true as boolean,
            isAsyncValidation: false as boolean,
            rule: string({
                required_error: validationMsg?.lastname?.required,
                invalid_type_error: validationMsg?.lastname?.invalid,
            }).optional(),
        },
        terms: {
            value: false as boolean,
            eventListener: "change",
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
            eventListener: "change",
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
        username: typeof formData.username.rule;
        email: typeof formData.email.rule;
        password: typeof formData.password.rule;
        repass: typeof formData.repass.rule;
        firstname: typeof formData.firstname.rule;
        lastname: typeof formData.lastname.rule;
        [key: string]: ZodType<any>;
    };

    const RegistrationValidators: RegistrationValidators = {
        username: formData.username.rule,
        email: formData.email.rule,
        password: formData.password.rule,
        repass: formData.repass.rule,
        firstname: formData.firstname.rule,
        lastname: formData.lastname.rule,
    };

    if (isTermsEnabled) {
        RegistrationValidators["terms"] = formData.terms.rule;
    }

    if (isPPEnabled) {
        RegistrationValidators["pp"] = formData.pp.rule;
    }

    if (isCaptchaEnabled) {
        RegistrationValidators["captcha"] = formData.pp.rule; // Todo: need to fix this.
    }

    const RegistrationFormSchema = object(RegistrationValidators);

    const registrationForm = document.getElementById(formID) as HTMLFormElement;

    const fields: string[] = Object.keys(RegistrationValidators);

    /**
     * Add event listeners to all the active fields.
     */
    fields.forEach((field) => {
        const fieldOption = formData[field as keyof typeof formData];
        if (fieldOption.active) {
            registrationForm
                ?.querySelector(`.swpm-registration-form-${field}`)
                ?.addEventListener(fieldOption.eventListener, (e) => {
                    handleDomEvent(e, field);
                });
        }
    });

    /**
     * Listen to form submit.
     */
    registrationForm?.addEventListener("submit", async function (e) {
        e.preventDefault();

        let validationSucess = true;

        for (const key in formData) {
            if (!formData[key as keyof typeof formData].active) {
                continue;
            }

            let isSuccess = await validateInput(
                key,
                formData[key as keyof typeof formData].value
            );

            if (!isSuccess) {
                validationSucess = false
            }
        }

        // Submits the form if all the validation is successful.
        if (validationSucess) {
            registrationForm.submit();
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
        if (formData[field as keyof typeof formData].isAsyncValidation) {
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

        formData[field as keyof typeof formData].value = inputValue;

        // Validates the input value
        validateInput(field, inputValue);
    }
});

/**
 * Asynchronously checks whether the field value exists on the database.
 *
 * @param value The value to retrieve
 * @param field The field to retrieve the value of.
 * @returns boolean
 */
async function checkAvailability(value: string, field: string) {
    // Checks whether the input field is empty or not. If empty, then return false in order to show no validation error.
    if (value.trim() === "") {
        return true;
    }

    // append the field value
    swpmRegFormAjax.query_args.action =
        field === "username"
            ? "swpm_validate_user_name"
            : "swpm_validate_email";
    swpmRegFormAjax.query_args.fieldValue = value;

    const queryString = new URLSearchParams(
        swpmRegFormAjax.query_args
    ).toString();
    const apiUrl = swpmRegFormAjax.ajax_url + "?" + queryString;
    console.log(apiUrl);

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


    // return new Promise((resolve) => {
    //     const database = {
    //         username: "john",
    //         email: "john@email.com",
    //     };
    //     setTimeout(() => {
    //         const exists = value !== database[field as keyof typeof database];
    //         resolve(exists);
    //     }, 1000);
    // });

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
    return registrationForm?.querySelector(`.swpm-registration-${field}-row`);
}

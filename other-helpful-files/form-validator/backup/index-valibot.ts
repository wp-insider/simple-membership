import { z } from "zod";
// import { fromZodError } from "zod-validation-error";
import {object, string, minLength, email , type Output, safeParse} from 'valibot';

document.addEventListener("DOMContentLoaded", function () {

	// Defining all validation rules
	const RegistrationFormSchema =  object({
		username: string([minLength(4)]),
		email: string([email()]),
		pass: string([minLength(8)]),
		repass: string([minLength(8)]),
		firstname: string(),
		lastname: string(),
	  });
	type FormData = Output<typeof RegistrationFormSchema>;;
	const formData: FormData = {
		username: "",
		email: "",
		pass: "",
		repass: "",
		firstname: "",
		lastname: "",
	}

	const registrationForm = document.getElementById('swpm-registration-form');

	const formfields: string[] = [
		'username', 'email', 'pass', 'repass', 'firstname', 'lastname'
	]
	formfields.forEach(field => {
		registrationForm?.querySelector(`.swpm-registration-form-${field}`)?.addEventListener('keyup', (e) => {
			const target = e.target as HTMLInputElement; // Type assertion
			formData[field] = target.value
			console.log(formData[field]);
		});
	})

	/**
	 * Get the child inside the target form.
	 */
	const formFields = registrationForm?.querySelectorAll('.swpm-registration-form-field');
	formFields?.forEach(field => {
		field.addEventListener('keyup', (e) => {
			console.log("Keyup occured");
		})
	})

	registrationForm?.addEventListener('submit', function (e) {
		e.preventDefault();

		const result = safeParse(RegistrationFormSchema, formData);
		if (!result.success) {
			const issues = result.issues;
			console.log(result);
			console.log(result.issues);
			// console.log(fromZodError(result.error));

			for (const i in issues) {
				const error = issues[i];
				// Handle each error here
				const errorMsg: string = error.message;
				const path: string | number = error.path[0];
				const targetField = registrationForm.querySelector(`.swpm-registration-${path}-row`);

				const targetFieldDesc = targetField?.querySelector(`.swpm-registration-${path}-desc`)
				if (targetFieldDesc) {
					targetFieldDesc.innerHTML = errorMsg;
				}
			}
		} else {

			console.log(result);
		}

		// alert('Form submitted.');
	});
})
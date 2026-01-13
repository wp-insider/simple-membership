/* global form_id, validationMsg */

import {z} from "zod";

/**
 * Defining variables that would come from PHP.
 */

document.addEventListener("DOMContentLoaded", function () {
    // @ts-ignore
    const formID = typeof form_id !== "undefined" ? form_id : "swpm-create-level";

    const membershipLevelSchema = z.object({
        // @ts-ignore
        alias: z.string().trim().min(1, validationMsg?.membershipLevelAlias?.required),
    });

    type MembershipLevelData = z.infer<typeof membershipLevelSchema>;

    const form = document.getElementById(formID) as HTMLFormElement | null;
    form?.addEventListener('submit', function (e) {
        e.preventDefault();

        const result = validateInputs(this);

        if (!result.success) {
            showErrors(result.error);
            return;
        }

        this.submit(); // Continue form submission.
    });

    function validateInputs(form: HTMLFormElement) {
        clearErrors(form);

        const formData = new FormData(form);

        const data: MembershipLevelData = {
            alias: String(formData.get('alias') ?? ""),
        }

        return membershipLevelSchema.safeParse(data);
    }

    function clearErrors(form: HTMLFormElement): void {
        form.querySelectorAll<HTMLElement>(".swpm-membership-level-field-error").forEach(el => {
            el.remove();
        });

        form.querySelectorAll<HTMLElement>('td.error').forEach((el) => {
            el.classList.remove('error');
        })
    }

    function showErrors(error: z.ZodError<MembershipLevelData>): void {
        error.errors.forEach((err, index) => {
            const fieldName = err.path[0] as keyof MembershipLevelData;

            const errField = document.querySelector<HTMLElement>(
                `[name="${fieldName}"]`
            );

            const errFieldCont = errField?.parentElement;

            let errMsgCont = document.querySelector(`ul.swpm-membership-level-${fieldName}-error`);

            if (!errMsgCont) {
                errMsgCont = document.createElement('ul');
                errMsgCont.classList.add(`swpm-membership-level-field-error`);
                errMsgCont.classList.add(`swpm-membership-level-${fieldName}-error`);
            }

            let errEl = document.createElement('li');
            errEl.textContent = err.message;
            errMsgCont.appendChild(errEl);

            if (errFieldCont) {
                errFieldCont.classList.add('error');
                errFieldCont.appendChild(errMsgCont);
            }

            // Focus and scroll to error input
            if (errField && index === 0 ) {
                errField.focus();
                scrollIntoViewIfNeeded(errField);
            }

            errField?.addEventListener("change", function () {
                const currentForm = errField?.closest('form');
                if (currentForm){
                    const result = validateInputs(currentForm);

                    if (!result.success) {
                        showErrors(result.error);
                        return;
                    }
                }
            });
        });
    }

    function scrollIntoViewIfNeeded(
        element: HTMLElement,
        options: ScrollIntoViewOptions = { behavior: "smooth", block: "center" }
    ): void {
        const rect = element.getBoundingClientRect();
        const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

        const isInView =
            rect.top >= 0 &&
            rect.bottom <= viewportHeight;

        if (!isInView) {
            element.scrollIntoView(options);
        }
    }

});

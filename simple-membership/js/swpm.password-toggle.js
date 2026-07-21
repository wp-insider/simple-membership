/* global swpmPasswordToggleStrings */

class SWPM_Password_Visibility_Toggler {

    passwordInputSelectorsToAttach = '';

    iconClassNames = [
        'swpm-form-input-icon',
        'swpm-form-input-icon-password'
    ];

    allowTabNavigation = true;

    constructor(options) {
        const defaults = {
            formId : '',
            type : 'checkbox',
            checkboxTogglerSelector : '',
            checkboxTogglerStyles : {},
            passwordInputSelectors : 'input[type="password"]',
            passwordInputSelectorsToAttach : 'input[type="password"]',
        };
        options = {
            ...defaults,
            ...options,
        };

        this.type = options.type.trim().toLowerCase();
        this.form = document.getElementById(options.formId.trim());
        if (!this.form) {
            console.warn('WARNING: Target form not found. Password toggler cannot be loaded.');
            return;
        }

        this.checkboxTogglerSelector = options.checkboxTogglerSelector.trim();
        this.checkboxTogglerStyles = options.checkboxTogglerStyles;

        this.passwordInputSelectors = options.passwordInputSelectors.trim();
        if (this.passwordInputSelectors) {
            this.passwordInputs = this.form.querySelectorAll(this.passwordInputSelectors);
            if ( ! this.passwordInputs ){
                console.warn('WARNING: No Password input fields found.');
                return;
            }
        }

        this.passwordInputSelectorsToAttach = options.passwordInputSelectorsToAttach.trim();
        if (this.passwordInputSelectorsToAttach) {
            this.passwordInputsToAttach = this.form.querySelectorAll(this.passwordInputSelectorsToAttach);
            if ( ! this.passwordInputsToAttach ){
                console.warn('WARNING: Password input fields not found to attach toggler to.');
                return;
            }
        }

        this.init();
    }

    init(){
        switch(this.type){
            case "icon":
                this.init_icon_type();
                break;
            case "checkbox":
            default:
                this.init_checkbox_type();
                break;
        }
    }

    init_checkbox_type(){
        // For login form (backward compatible).
        if(this.checkboxTogglerSelector.length){
            const checkbox = this.form.querySelector(this.checkboxTogglerSelector);
            checkbox?.addEventListener('change', this.onCheckboxChange);

            return;
        }

        // For registration and profile form.
        this.passwordInputsToAttach.forEach(input => {
            const checkbox = this.createPasswordToggle();

            for (const [style, value] of Object.entries(this.checkboxTogglerStyles)) {
                checkbox.style[style] = value;
            }

            const wrap = input.parentNode;
            wrap.insertBefore(checkbox, input.nextSibling);

            checkbox.addEventListener('change', this.onCheckboxChange);
        })
    }


    init_icon_type(){
        this.allowTabNavigation = false;

        this.passwordInputsToAttach.forEach((input) => {
            const inputParent = input.parentElement;
            inputParent.style.position = 'relative';

            const icon = document.createElement('span');
            icon.classList.add(
                ...this.iconClassNames
            );

            const eyeIcon = this.createEyeIcon();
            const eyeSlashIcon = this.createEyeSlashIcon();

            icon.appendChild(eyeIcon);
            icon.appendChild(eyeSlashIcon);

            icon.style.color = '#4d4d4d';

            // Uncomment this to enable support for tab navigation and screen readers.
            if (this.allowTabNavigation) {
                icon.setAttribute('role', 'button');
                icon.setAttribute('tabindex', '0');
                icon.setAttribute('aria-label', 'Show/Hide Password Button');
            }

            inputParent.appendChild(icon);

            this.positionPasswordIcon(icon);

            // Listen to click events
            icon.addEventListener('click', this.onIconClick);
        });

        // Re-run on resize in case theme is responsive and input height changes
        window.addEventListener('resize', this.positionPasswordIcons);

        // const observer = new ResizeObserver((entries) => {
        //     this.positionPasswordIcons();
        // });
        // observer.observe(this.form);

        // Re-run after fonts load, since font-loading can shift input height
        if (document.fonts) {
            document.fonts.ready.then(this.positionPasswordIcons);
        }
    }

    onCheckboxChange = (e) => {
        const isChecked = e.target.checked;

        // Reveal/hide all password inputs.
        this.passwordInputs.forEach((input) => {
            input.type = isChecked ? 'text' : 'password';
        })
    }

    onIconClick = (e) => {
        let icon = e.target;
        if (!e.target.matches('.swpm-form-input-icon')) { // Check if user clocked on svg/path, not the wrapper span
            icon = e.target.closest('.swpm-form-input-icon');
        }

        // const input = icon.closest('.swpm-form-input-wrap').querySelector('input.swpm-form-field');
        const input = icon.parentNode.querySelector( 'input' );

        const isPasswordType = input.type === 'password';
        input.type = isPasswordType ? 'text' : 'password';

        const iconSVGs = icon.querySelectorAll('svg');
        iconSVGs.forEach(svg => {
            const isHidden = svg.style.display === 'none';
            if (isHidden){
                svg.style.display = 'inline';
            } else {
                svg.style.display = 'none';
            }
        });

        // window.dispatchEvent(new Event('resize'));
    }

    positionPasswordIcons = () => {
        const iconSelector = this.getClassSelectorFromArray(this.iconClassNames);
        const passToggleIcons = document.querySelectorAll(iconSelector);

        passToggleIcons.forEach(this.positionPasswordIcon);
    }

    positionPasswordIcon = (icon) =>{
        if (!icon) {
            return;
        }

        const wrap = icon.parentNode;

        const input = wrap.querySelector(this.passwordInputSelectorsToAttach);
        if (!input) {
            return;
        }

        // Make wrapper the positioning context, without assuming theme CSS
        const wrapStyle = getComputedStyle(wrap);
        if (wrapStyle.position === 'static') {
            wrap.style.position = 'relative';
        }

        // Total rendered height of the input (content + padding + border)
        const inputHeight = input.offsetHeight;

        // Position icon: vertically centered relative to input's box,
        // accounting for input's own offsetTop within the wrapper
        const iconHeight = icon.offsetHeight || 16; // fallback if not yet rendered
        const verticalCenter = input.offsetTop + (inputHeight / 2) - (iconHeight / 2);

        const inputOffsetRight = input.offsetParent.offsetWidth - (input.offsetLeft + input.offsetWidth);

        icon.style.zIndex = '2';
        icon.style.position = 'absolute';
        icon.style.top = verticalCenter + 'px';
        icon.style.right = inputOffsetRight + 'px'; // distance from right edge
        icon.style.display = 'inline-flex';
        icon.style.alignItems = 'center';
        icon.style.justifyContent = 'center';
        icon.style.background = 'transparent none !important';
        icon.style.height = inputHeight + 'px';
        icon.style.padding = '0 1rem';
        icon.style.cursor = 'pointer';

        const iconSVGHeight = inputHeight * 0.5 + 'px';
        const iconSVGWidth = inputHeight * 0.7 + 'px';

        const iconSVGs = wrap.querySelectorAll('svg');
        iconSVGs.forEach(svg => {
            svg.setAttribute('height', iconSVGHeight);
            svg.setAttribute('width', iconSVGWidth);
        })

    }

    createEyeIcon() {
        const SVG_NS = 'http://www.w3.org/2000/svg';
        const svg = document.createElementNS(SVG_NS, 'svg');
        svg.setAttribute('viewBox', '0 0 16 16');
        svg.classList.add('swpm-eye');
        svg.setAttribute('fill', 'currentColor');
        svg.style.display = 'inline';

        const path1 = document.createElementNS(SVG_NS, 'path');
        path1.setAttribute(
            'd',
            'm10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0'
        );

        const path2 = document.createElementNS(SVG_NS, 'path');
        path2.setAttribute(
            'd',
            'M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7'
        );

        svg.append(path1, path2);

        return svg;
    }

    createEyeSlashIcon() {
        const SVG_NS = 'http://www.w3.org/2000/svg';
        const svg = document.createElementNS(SVG_NS, 'svg');
        svg.setAttribute('viewBox', '0 0 16 16');
        svg.classList.add('swpm-eyeslash');
        svg.setAttribute('fill', 'currentColor');
        svg.style.display = 'none';

        const path1 = document.createElementNS(SVG_NS, 'path');
        path1.setAttribute(
            'd',
            'M10.79 12.912 9.176 11.297A3.5 3.5 0 0 1 4.702 6.823L2.642 4.763C.938 6.278 0 8 0 8s3 5.5 8 5.5a7 7 0 0 0 2.79-.588M5.21 3.088A7 7 0 0 1 8 2.5c5 0 8 5.5 8 5.5s-.939 1.721-2.641 3.238L11.297 9.176A3.5 3.5 0 0 0 6.823 4.702Z'
        );

        const path2 = document.createElementNS(SVG_NS, 'path');
        path2.setAttribute(
            'd',
            'm5.525 7.646a2.5 2.5 0 0 0 2.829 2.829Zm4.95.708-2.829-2.83a2.5 2.5 0 0 1 2.829 2.829Zm3.171 6-12-12 .708-.708 12 12Z'
        );

        svg.append(path1, path2);

        return svg;
    }

    createPasswordToggle() {
        const wrapper = document.createElement('div');
        wrapper.className = 'swpm-password-input-visibility';

        const label = document.createElement('label');
        label.className = 'swpm-password-toggle-checkbox-label';

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'swpm-password-toggle-checkbox';

        const span = document.createElement('span');
        span.className = 'swpm-password-toggle-label';
        span.textContent = swpmPasswordToggleStrings.showPassword;

        label.append(checkbox, span);
        wrapper.appendChild(label);

        return wrapper;
    }

    /**
     * Converts array of class names into something like .className1.className2 for querySelector operation.
     *
     * @param classnames {array}
     * @returns {string}
     */
    getClassSelectorFromArray(classnames){
        return classnames.map(str => '.'+str.trim()).join('');
    }
}
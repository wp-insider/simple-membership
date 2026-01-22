class SWPMDayMonthSelector {

    constructor(selector) {
        this.selector = selector;

        // Getting all months data (i.e. number of days, name of the month etc. of each month) form PHP side.
        const monthsData = selector.getAttribute('data-day-month-options');
        this.monthsData = JSON.parse(monthsData);

        this.monthSelectInput = selector.querySelector('.swpm-month-selector');
        this.daySelectInput = selector.querySelector('.swpm-day-selector');

        this.monthSelectInput?.addEventListener('change', this.onMonthChange);
    }

    /**
     * When the month select fields input changes, do the followings:
     * - Update the days select input field options according the number of days the selected month have.
     * - If the exising selected day is out of range for new selected month, resent the selected day value.
     */
    onMonthChange = () => {
        const selectedDay = this.daySelectInput?.value ? parseInt(this.daySelectInput?.value) : 1;
        const selectedMonth = this.monthSelectInput?.value ? parseInt(this.monthSelectInput?.value) : 1;
        const daysOfMonth = this.daysOfMonth(selectedMonth);

        const currentDaysOptions = this.daySelectInput?.querySelectorAll('option');

        if (selectedDay > daysOfMonth){
            this.daySelectInput.selectedIndex = 0;
        }

        const totalOptions = currentDaysOptions?.length;

        const diff = Math.abs(daysOfMonth - totalOptions);

        if (daysOfMonth > totalOptions){
            // Add new html option elements.
            this.appendNewOptions(diff);
        } else if (daysOfMonth < totalOptions) {
            // Remove last html option element(s) and set select input value to the first option.
            this.removeLastOptions(diff);
        } else {
            // Nothing to do
        }
    }

    /**
     * Returns how many days a particular month have.
     *
     * @param m {number} The calendar month number.
     * @returns {number} The number of days the month have.
     */
    daysOfMonth( m ){
        const monthData = this.monthsData[ m-1 ];

        return monthData['days'] ? parseInt(monthData['days']) : 0;
    }

    /**
     * Removes n number of last options from day select input field.
     *
     * @param n {number} Number of options to remove.
     */
    removeLastOptions = ( n ) => {
        while (n-- > 0 && this.daySelectInput?.lastElementChild) {
            this.daySelectInput?.lastElementChild.remove();
        }
    }

    /**
     * Appends n number of new days options the days input field.
     *
     * @param n {number} Number of options to add.
     */
    appendNewOptions = ( n ) => {
        const currentTotalOptions = this.daySelectInput.length;

        for (let i = 1; i <= n; i++){
            const optionEl = document.createElement('option');
            optionEl.value = currentTotalOptions + i;
            optionEl.textContent = currentTotalOptions + i;

            this.daySelectInput.appendChild(optionEl);
        }
    }
}

document.addEventListener('DOMContentLoaded', function (){
    const selectors = document.querySelectorAll('.swpm-month-day-selector');
    selectors.forEach(selector => {
        // Initiate month day selector.
        new SWPMDayMonthSelector(selector);
    })
})
<script>
    (function () {
        const countSelect = document.getElementById('answers_count');
        if (!countSelect) return;

        const answerFields = document.querySelectorAll('.answer-field');
        const correctOptions = document.querySelectorAll('.correct-answer-option');
        const correctSelect = document.getElementById('correct_answer_select');

        function apply() {
            const count = parseInt(countSelect.value, 10);

            answerFields.forEach((field) => {
                const idx = parseInt(field.dataset.answerIndex, 10);
                const visible = idx < count;
                field.classList.toggle('d-none', !visible);
                const input = field.querySelector('input');
                if (input) {
                    input.required = visible;
                    if (!visible) input.value = '';
                }
            });

            let selectedStillValid = false;
            correctOptions.forEach((option) => {
                const idx = parseInt(option.dataset.answerIndex, 10);
                const visible = idx < count;
                option.classList.toggle('d-none', !visible);
                option.disabled = !visible;
                if (visible && option.selected) selectedStillValid = true;
            });

            if (!selectedStillValid) {
                correctSelect.value = '';
            }
        }

        countSelect.addEventListener('change', apply);
        apply();
    })();
</script>

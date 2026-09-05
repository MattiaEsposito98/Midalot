<script>
    (function () {
        const radios = document.querySelectorAll('.content-mode-radio');
        const textFields = document.querySelectorAll('.mode-field-testo');
        const imageFields = document.querySelectorAll('.mode-field-immagine');

        function applyMode() {
            const checked = document.querySelector('.content-mode-radio:checked');
            const mode = checked ? checked.value : 'testo';
            const isTesto = mode === 'testo';

            textFields.forEach((field) => {
                field.classList.toggle('d-none', !isTesto);
                const input = field.querySelector('input');
                if (input) input.required = isTesto;
            });

            imageFields.forEach((field) => {
                field.classList.toggle('d-none', isTesto);
                const fileInput = field.querySelector('input[type="file"]');
                const removeCheckbox = field.querySelector('input[type="checkbox"]');
                const hasExisting = field.dataset.hasImage === '1' && !(removeCheckbox && removeCheckbox.checked);
                if (fileInput) fileInput.required = !isTesto && !hasExisting;
            });
        }

        radios.forEach((radio) => radio.addEventListener('change', applyMode));
        imageFields.forEach((field) => {
            const removeCheckbox = field.querySelector('input[type="checkbox"]');
            if (removeCheckbox) removeCheckbox.addEventListener('change', applyMode);
        });

        applyMode();
    })();
</script>

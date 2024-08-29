document.addEventListener('DOMContentLoaded', function() {
    const langSwitcher = document.getElementById('lang_switch');

    function loadLanguage(lang) {
        fetch(`/languages/${lang}.json`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Failed to load language file: ${lang}`);
                }
                return response.json();
            })
            .then(translations => {
                console.log('Loaded translations:', translations); 
                document.querySelectorAll('[data-translate]').forEach(element => {
                    const key = element.getAttribute('data-translate');
                    if (translations[key]) {
                        if (element.tagName === 'OPTION') {
                            element.innerHTML = translations[key];
                        } else {
                            element.textContent = translations[key];
                        }
                    } else {
                        console.warn(`Key not found: ${key}`);
                        element.textContent = `Key not found: ${key}`;
                    }
                });
            })
            .catch(error => console.error('Error loading the translation file:', error));
    }

    langSwitcher.addEventListener('change', function() {
        loadLanguage(this.value);
    });

    loadLanguage('fr');
});

let npo = (() => {
    async function translate(language, text) {
        const payload = {
            "id": 25,
            "jsonrpc": "2.0",
            "method": "LMT_handle_jobs",
            "params": {
                "jobs": [
                    {
                        "kind": "default",
                        "raw_en_sentence": text
                    }
                ],
                "lang": {
                    "source_lang_computed": "NL",
                    "target_lang": language,
                    "user_preferred_langs": [
                        "FR", "ES", "DE", "EN", "NL"
                    ],
                    "priority": 1
                }
            }
        };

        const translationResponse = await fetch("https://cors-anywhere.herokuapp.com/https://www.deepl.com/jsonrpc", {
            method: "POST",
            body: JSON.stringify(payload)
        });

        let translation = (await translationResponse.json())["result"]["translations"][0]["beams"][0]["postprocessed_sentence"];
        translation = translation.replace(/\.{3,}/, "...");
        return translation;
    }

    async function getJson(url) {
        return (await fetch(url, {
            headers: {
                "ApiKey": "e45fe473feaf42ad9a215007c6aa5e7e"
            }
        })).json();
    }

    return {
        translate: translate,
        getJson: getJson
    }
})();
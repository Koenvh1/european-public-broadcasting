class npo {
    static async translate(language, text) {
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

        const translationResponse = await fetch("translate.php", {
            method: "POST",
            body: JSON.stringify(payload)
        });

        let translation = (await translationResponse.json())["result"]["translations"][0]["beams"][0]["postprocessed_sentence"];
        translation = translation.replace(/\.{3,}/, "...");
        return translation;
    }

    static async ocr(image) {
        const payload = {
            "requests": [
                {
                    "features": [
                        {
                            "maxResults": 1,
                            "type": "DOCUMENT_TEXT_DETECTION"
                        }
                    ],
                    "image": {
                        "content": image
                    },
                    "imageContext": {
                        "languageHints": [
                            "nl", "en"
                        ]
                    }
                }
            ]
        };

        const response = await fetch("https://cxl-services.appspot.com/proxy?url=https%3A%2F%2Fvision.googleapis.com%2Fv1%2Fimages%3Aannotate", {
            method: "POST",
            body: JSON.stringify(payload)
        });

        let intermediate = (await response.json())["responses"][0];
        if (intermediate.hasOwnProperty("textAnnotations")) {
            return intermediate["textAnnotations"][0]["description"];
        } else {
            return null;
        }
    }

    static async getJson(url) {
        return (await fetch(url, {
            headers: {
                "ApiKey": "e45fe473feaf42ad9a215007c6aa5e7e"
            }
        })).json();
    }
}
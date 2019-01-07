class npo {
    static async translate(language, text) {
        // const payload = {
        //     "id": 91510002,
        //     "jsonrpc": "2.0",
        //     "method": "LMT_handle_jobs",
        //     "params": {
        //         "jobs": [
        //             {
        //                 "kind": "default",
        //                 "raw_en_sentence": text
        //             }
        //         ],
        //         "lang": {
        //             "source_lang_computed": "NL",
        //             "target_lang": language,
        //             "user_preferred_langs": [
        //                 "FR", "ES", "DE", "EN", "NL"
        //             ],
        //         },
        //         "priority": -1,
        //         "timestamp": Date.now()
        //     }
        // };
        //
        // const translationResponse = await fetch("translate.php", {
        //     method: "POST",
        //     body: JSON.stringify(payload)
        // });
        //
        // let translation = (await translationResponse.json())["result"]["translations"][0]["beams"][0]["postprocessed_sentence"];
        let translation = await (await fetch("translate.php", {
            method: "POST",
            body: JSON.stringify({"target": language, "text": text})
        })).json();
        translation = translation["result"];

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
            let annotation = intermediate["textAnnotations"][0];
            if ((annotation["boundingPoly"]["vertices"][2]["y"] - annotation["boundingPoly"]["vertices"][0]["y"]) > 25) {
                return annotation["description"];
            }
        }
        return null;
    }

    static async getJson(url) {
        return (await fetch(url, {
            headers: {
                "ApiKey": "e45fe473feaf42ad9a215007c6aa5e7e"
            }
        })).json();
    }

    static getLanguages() {
        return {
            'af': 'Afrikaans',
            'sq': 'Albanian',
            'am': 'Amharic',
            'ar': 'Arabic',
            'hy': 'Armenian',
            'az': 'Azerbaijani',
            'eu': 'Basque',
            'be': 'Belarusian',
            'bn': 'Bengali',
            'bs': 'Bosnian',
            'bg': 'Bulgarian',
            'ca': 'Catalan',
            'ceb': 'Cebuano',
            'ny': 'Chichewa',
            'zh-cn': 'Chinese Simplified',
            'zh-tw': 'Chinese Traditional',
            'co': 'Corsican',
            'hr': 'Croatian',
            'cs': 'Czech',
            'da': 'Danish',
            'nl': 'Dutch',
            'en': 'English',
            'eo': 'Esperanto',
            'et': 'Estonian',
            'tl': 'Filipino',
            'fi': 'Finnish',
            'fr': 'French',
            'fy': 'Frisian',
            'gl': 'Galician',
            'ka': 'Georgian',
            'de': 'German',
            'el': 'Greek',
            'gu': 'Gujarati',
            'ht': 'Haitian Creole',
            'ha': 'Hausa',
            'haw': 'Hawaiian',
            'iw': 'Hebrew',
            'hi': 'Hindi',
            'hmn': 'Hmong',
            'hu': 'Hungarian',
            'is': 'Icelandic',
            'ig': 'Igbo',
            'id': 'Indonesian',
            'ga': 'Irish',
            'it': 'Italian',
            'ja': 'Japanese',
            'jw': 'Javanese',
            'kn': 'Kannada',
            'kk': 'Kazakh',
            'km': 'Khmer',
            'ko': 'Korean',
            'ku': 'Kurdish (Kurmanji)',
            'ky': 'Kyrgyz',
            'lo': 'Lao',
            'la': 'Latin',
            'lv': 'Latvian',
            'lt': 'Lithuanian',
            'lb': 'Luxembourgish',
            'mk': 'Macedonian',
            'mg': 'Malagasy',
            'ms': 'Malay',
            'ml': 'Malayalam',
            'mt': 'Maltese',
            'mi': 'Maori',
            'mr': 'Marathi',
            'mn': 'Mongolian',
            'my': 'Myanmar (Burmese)',
            'ne': 'Nepali',
            'no': 'Norwegian',
            'ps': 'Pashto',
            'fa': 'Persian',
            'pl': 'Polish',
            'pt': 'Portuguese',
            'ma': 'Punjabi',
            'ro': 'Romanian',
            'ru': 'Russian',
            'sm': 'Samoan',
            'gd': 'Scots Gaelic',
            'sr': 'Serbian',
            'st': 'Sesotho',
            'sn': 'Shona',
            'sd': 'Sindhi',
            'si': 'Sinhala',
            'sk': 'Slovak',
            'sl': 'Slovenian',
            'so': 'Somali',
            'es': 'Spanish',
            'su': 'Sundanese',
            'sw': 'Swahili',
            'sv': 'Swedish',
            'tg': 'Tajik',
            'ta': 'Tamil',
            'te': 'Telugu',
            'th': 'Thai',
            'tr': 'Turkish',
            'uk': 'Ukrainian',
            'ur': 'Urdu',
            'uz': 'Uzbek',
            'vi': 'Vietnamese',
            'cy': 'Welsh',
            'xh': 'Xhosa',
            'yi': 'Yiddish',
            'yo': 'Yoruba',
            'zu': 'Zulu'
        };
    }
}
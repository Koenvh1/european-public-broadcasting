class Player {
    constructor() {
        this.videoPlayer = document.querySelector("#videoPlayer");
        this.errorModal = document.querySelector("#errorModal");
        this.language = localStorage.getItem("language") || "en";
        this.translated = {};
        this.languages = Object.entries(Utils.getLanguages()).map(lang => {
            this.translated[lang[0]] = [];
            return {
                code: lang[0],
                name: lang[1],
            }
        });
        //this.translated = Object.entries(Utils.getLanguages()).reduce((map, lang) => (map[lang[0]] = [], map), {});

        this.series = null;
        this.seasonId = null;
        this.lastOcr = null;

        this.customVideoUrl = null;
        this.customCaptionUrl = null;
        this.protectionData = null;
    }

   async loadSubtitles(url) {
        let subtitleResponse = await fetch(url);
        let blob = new Blob([(await subtitleResponse.text())], {type: "text/vtt"});
        let blobUrl = URL.createObjectURL(blob);

        this.languages.forEach(language => {
            let track = document.createElement("track");
            track.kind = "subtitles";
            track.label = language.name; //"Captions"; //this.languages.find(l => l.code === this.language).name;
            track.srclang = language.code;//this.language;
            track.src = blobUrl;

            this.videoPlayer.appendChild(track);
        });
    }

    async translateSubtitles(track, start) {
        start = parseInt(start);
        let cues = track.cues;

        let newItems = [];

        for (let i = (start - 1); i < (start + 2); i++) {
            if (cues[i] == null) continue;
            // Make sure it has an ID, and is not OCR
            if (!cues[i].id) continue;
            // Get the next five that have NOT been translated yet.
            if (this.translated[this.language].includes(cues[i].id)) continue;

            newItems.push(i);
        }

        for (let i = (start - 1); i < (start + 2); i++) {
            // Only translate the next five that have not been translated.
            if (!newItems.includes(i)) continue;
            const cue = cues[i];

            if (!cue.text) return; // Cue text can be empty on mistake (when still processing), skip it then.
            this.translated[this.language].push(cue.id); // Mark this as translated.

            let text = cue.text.replace(/\s\s+/g, ' '); // Without multiple spaces
            //console.log(cue.text);
            cue.text = "";

            cue.text = await Utils.translate(this.language, text);
        }
    }

    getCurrentTrack() {
        for (let i = 0; i < this.videoPlayer.textTracks.length; i++) {
            let t = this.videoPlayer.textTracks[i];
            if (t.language === this.language) {
                return t;
            }
        }
        return this.videoPlayer.textTracks[0];
    }

    setLanguage(language) {
        this.language = language;

        let track = this.getCurrentTrack();
        if (track != null && track.cues != null) {
            this.translateSubtitles(track, 0);
        }
    }

    hideCaptions() {
        for (let i = 0; i < this.videoPlayer.textTracks.length; i++) {
            if (this.videoPlayer.textTracks[i].mode !== "hidden") {
                this.videoPlayer.textTracks[i].mode = "hidden";
            }
        }
        // this.videoPlayer.textTracks[this.videoPlayer.textTracks.length - 1].mode = "hidden";
    }

    async load(video, caption, protection) {
        this.videoPlayer.addEventListener("loadedmetadata", () => {
            this.hideCaptions();
        });

        this.videoPlayer.addEventListener("canplay", () => {
            if (isNaN(this.getCurrentTrack().cues[0].id) || this.getCurrentTrack().cues[0].id === "") {
                for (let i = 0; i < this.getCurrentTrack().cues.length; i++) {
                    this.getCurrentTrack().cues[i].id = i;
                }
            }
        });

        this.videoPlayer.addEventListener("error", (e) => {
            this.errorModal.style.display = "initial";
        });
        this.customVideoUrl = video;
        this.customCaptionUrl = caption;
        this.protectionData = protection;

        if (this.customCaptionUrl) {
            await this.loadSubtitles(this.customCaptionUrl);
        }
        if (this.customVideoUrl.indexOf(".m3u8") !== -1) {
            if(Hls.isSupported()) {
                const hls = new Hls();
                hls.loadSource(this.customVideoUrl);
                hls.attachMedia(this.videoPlayer);
                hls.on(Hls.Events.MANIFEST_PARSED, () => this.videoPlayer.play());
            }
        } else {
            if (this.customVideoUrl.indexOf(".mpd") !== -1 || this.customVideoUrl.indexOf("dash") !== -1) {
                const dashjsPlayer = dashjs.MediaPlayer().create();
                if (this.protectionData) {
                    dashjsPlayer.setProtectionData(this.protectionData);
                }
                dashjsPlayer.initialize(this.videoPlayer, this.customVideoUrl, true);
                //dashjsPlayer.attachTTMLRenderingDiv(document.getElementById("subtitles"));
            } else {
                this.videoPlayer.src = this.customVideoUrl;
            }
        }

        setInterval(async () => {
            let track = this.getCurrentTrack();
            if (track != null && track.activeCues != null && track.activeCues.length > 0) {
                for (let i = 0; i < track.activeCues.length; i++) {
                    let id = parseInt(track.activeCues[i]["id"]);
                    if (!isNaN(id)) {
                        await this.translateSubtitles(track, Object.keys(track.cues).find(key => track.cues[key]["id"] === track.activeCues[i]["id"]));
                    }
                }
            }
        }, 500);
    }
}

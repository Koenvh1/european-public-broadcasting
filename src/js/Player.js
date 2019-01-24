class Player {
    constructor() {
        this.videoPlayer = document.querySelector("#videoPlayer");
        this.language = localStorage.getItem("language") || "en";
        this.languages = Object.entries(Utils.getLanguages()).map(lang => {
            return {
                code: lang[0],
                name: lang[1],
            }
        });
        this.translated = Object.entries(Utils.getLanguages()).reduce((map, lang) => (map[lang[0]] = [], map), {});

        this.series = null;
        this.seasonId = null;
        this.lastOcr = null;

        this.customVideoUrl = null;
        this.customCaptionUrl = null;
    }

    async getVideoUrl(videoId) {
        let tokenResponse = await
            fetch("https://ida.omroep.nl/app.php/auth");
        let token = (await tokenResponse.json())["token"];

        let linkResponse = await
            fetch("https://ida.omroep.nl/app.php/" + videoId + "?adaptive=yes&token=" + token);
        let items = (await linkResponse.json())["items"][0];
        let url = items.find(item => item["label"] === "Hoog")["url"];
        url = url.replace("callback=?", "callback=videoInfo");

        let script = document.createElement("script");
        script.src = url;
        document.body.appendChild(script);

        await this.loadSubtitles("https://rs.poms.omroep.nl/v1/api/subtitles/" + videoId + "/nl_NL/CAPTION.vtt");
    }

    async loadSubtitles(url) {
        let subtitleResponse = await fetch(url);
        let blob = new Blob([(await subtitleResponse.text())], {type: "text/vtt"});
        let blobUrl = URL.createObjectURL(blob);

        let track = document.createElement("track");
        track.kind = "subtitles";
        track.label = this.languages.find(l => l.code === this.language).name;
        track.srclang = this.language;
        track.src = blobUrl;

        this.videoPlayer.appendChild(track);
    }

    async videoInfo(callback) {
        if (callback.errorstring) {
            alert(await
                Utils.translate(localStorage.getItem("language") || "EN", callback.errorstring)
            )
        }
        this.videoPlayer.src = callback.url;
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
        return this.videoPlayer.textTracks[0];
    }

    async load() {
        NProgress.start();

        this.videoPlayer.addEventListener("loadedmetadata", () => {
            this.videoPlayer.textTracks[0].mode = "showing";
        });

        this.videoPlayer.addEventListener("canplay", () => {
            if (isNaN(this.getCurrentTrack().cues[0].id) || this.getCurrentTrack().cues[0].id === "") {
                for (let i = 0; i < this.getCurrentTrack().cues.length; i++) {
                    this.getCurrentTrack().cues[i].id = i;
                }
            }
        });

        this.videoPlayer.addEventListener("error", (e) => {
            console.log(this.videoPlayer.error.message);
        });
        const searchParams = new URL(window.location).searchParams;
        let videoId = searchParams.get("v");
        this.customVideoUrl = searchParams.get("videoUrl");
        this.customCaptionUrl = searchParams.get("captionUrl");
        let broadcaster = searchParams.get("broadcaster");
        if (broadcaster) {
            try {
                let urls = await Broadcaster.parseUrl(broadcaster, this.customVideoUrl);
                this.customVideoUrl = urls.video;
                this.customCaptionUrl = urls.subtitles;
            } catch (e) {
                alert(e);
            }
        }
        if (!this.customVideoUrl) {
            await this.getVideoUrl(videoId);

            let episode = await Utils.getJson("https://start-api.npo.nl/page/episode/" + videoId);

            this.series = episode["components"][0]["series"]["id"];
            //this.seasonId = episode["components"][0]["episode"]["seasons"][0]["id"];

            episode = episode["components"][0]["episode"];
            $(".series-title").text(episode["title"]);
            $(".series-episode-title").text(episode["episodeTitle"]);
            $(".series-broadcasters").text(episode["broadcasters"].join(", "));
            $(".series-date").text(`season ${episode["seasonNumber"]} episode ${episode["episodeNumber"]} - ` + new Date(Date.parse(episode["broadcastDate"])).toLocaleDateString());
            $(".series-description").html(await Utils.translate(localStorage.getItem("language") || "EN", episode["description"]));
            $(".series-channel").attr("src", "img/" + episode["channel"] + ".svg");
        } else {
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
                    /*
                    dashjsPlayer.setProtectionData({
                        "com.widevine.alpha": {
                            "serverURL": "https://npo-drm-gateway.samgcloud.nepworldwide.nl/authentication",
                            "httpRequestHeaders": {
                                "x-custom-data": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJucG8iLCJpYXQiOjE1NDgxNzE4MDYsImRybV90eXBlIjoid2lkZXZpbmUiLCJsaWNlbnNlX3Byb2ZpbGUiOiJ3ZWIiLCJjbGllbnRfaXAiOiI2Mi4xNjMuMjA2LjE4In0.qMdSl4PsH7WMp3uYfkO4VgTSQzWD7D5AZB0KJuCS9-k"
                            }
                        }
                    });
                    */
                    dashjsPlayer.initialize(this.videoPlayer, this.customVideoUrl, true);
                    //dashjsPlayer.attachTTMLRenderingDiv(document.getElementById("subtitles"));
                } else {
                    this.videoPlayer.src = this.customVideoUrl;
                }
            }

            $(".series-title").text("Unavailable");
            $(".series-episode-title").text("Unavailable");
            $(".series-broadcasters").text("Unavailable");
            $(".series-date").text("Unavailable");
            $(".series-description").text("Unavailable");
            $(".series-channel").text("Unavailable");
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

        setInterval(async () => {
            if (!this.videoPlayer.paused && $("#enableOcr").is(":checked")) {
                await this.ocr();
            }
        }, 2000);

        NProgress.done();
    }

    async ocr() {
        let canvas = document.createElement("canvas");
        let w = this.videoPlayer.videoWidth;
        let h = Math.round(this.videoPlayer.videoHeight / 4);
        canvas.width = w;
        canvas.height = h;

        let context = canvas.getContext("2d");
        context.drawImage(this.videoPlayer, 0, h * 3, w, h, 0, 0, w, h);
        let image = canvas.toDataURL("image/png");

        $("#ocr").attr("src", image);

        image = image.split(",")[1];
        let response = await Utils.ocr(image);
        if (response != null && this.lastOcr !== response) {
            this.lastOcr = response;
            const track = this.getCurrentTrack();
            let translation = await Utils.translate(this.language, response);
            //console.log(translation);

            /*
            for(let i = 0; i < this.videoPlayer.textTracks[1].activeCues.length; i++) {
                let cue = this.videoPlayer.textTracks[1].activeCues[i];
                if(cue.text === translation) {
                    cue.endTime += 2;
                    return;
                }
            }
            */
            track.addCue(new VTTCue(this.videoPlayer.currentTime, this.videoPlayer.currentTime + 2.2, translation));

        }
    }
}
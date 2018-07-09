class Player {
    constructor() {
        this.videoPlayer = document.querySelector("#videoPlayer");
        this.languages = [
            {
                code: "DE",
                name: "Deutsch"
            },
            {
                code: "EN",
                name: "English"
            },
            {
                code: "ES",
                name: "Español"
            },
            {
                code: "FR",
                name: "français"
            },
            {
                code: "IT",
                name: "Italiano"
            },
            {
                code: "PL",
                name: "Polski"
            },
        ];
        this.translated = {
            "DE": [],
            "EN": [],
            "ES": [],
            "FR": [],
            "IT": [],
            "PL": []
        };
        this.series = null;
        this.seasonId = null;
        this.lastOcr = null;
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

        let subtitleResponse = await fetch("https://rs.poms.omroep.nl/v1/api/subtitles/" + videoId + "/nl_NL/CAPTION.vtt");
        let blob = new Blob([(await subtitleResponse.text())], {type: "text/vtt"});
        let blobUrl = URL.createObjectURL(blob);

        this.languages.forEach(language => {
            let track = document.createElement("track");
            track.kind = "subtitles";
            track.label = language.name;
            track.srclang = language.code;
            track.src = blobUrl;

            this.videoPlayer.appendChild(track);
        });
    }

    async videoInfo(callback) {
        if (callback.errorstring) {
            alert(await
                npo.translate(localStorage.getItem("language") || "EN", callback.errorstring)
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
            if (this.translated[track.language].includes(cues[i].id)) continue;

            newItems.push(i);
        }

        for (let i = (start - 1); i < (start + 2); i++) {
            // Only translate the next five that have not been translated.
            if (!newItems.includes(i)) continue;
            const cue = cues[i];

            if (!cue.text) return; // Cue text can be empty on mistake (when still processing), skip it then.
            this.translated[track.language].push(cue.id); // Mark this as translated.

            let text = cue.text;
            //console.log(cue.text);
            cue.text = "";

            // Object.defineProperty(cue, 'text', {
            //     value: await npo.translate(track.language, text);,
            //     writable: true,
            //     enumerable: true,
            //     configurable: true
            // });
            //track.addCue(new VTTCue(cue.startTime, cue.endTime, await npo.translate(track.language, text)));
            cue.text = await npo.translate(track.language, text);
        }
    }

    getCurrentTrack() {
        let track = null;
        for (let i = 0; i < this.videoPlayer.textTracks.length; i++) {
            if (this.videoPlayer.textTracks[i].mode === "showing") {
                track = this.videoPlayer.textTracks[i];
                break;
            }
        }
        return track;
    }

    async load() {
        NProgress.start();

        this.videoPlayer.addEventListener("loadedmetadata", () => {
            let index = this.languages.findIndex(l => l.code === localStorage.getItem("language"));
            if (index < 0) index = 1;
            this.videoPlayer.textTracks[index].mode = "showing";
        });

        let videoId = new URL(window.location).searchParams.get("v");
        await this.getVideoUrl(videoId);

        let episode = await npo.getJson("https://start-api.npo.nl/page/episode/" + videoId);

        this.series = episode["components"][0]["series"]["id"];
        //this.seasonId = episode["components"][0]["episode"]["seasons"][0]["id"];

        episode = episode["components"][0]["episode"];
        $(".series-title").text(episode["title"]);
        $(".series-episode-title").text(episode["episodeTitle"]);
        $(".series-broadcasters").text(episode["broadcasters"].join(", "));
        $(".series-date").text(`season ` + episode["seasonNumber"] + ` episode ` + episode["episodeNumber"] + ` - ` + new Date(Date.parse(episode["broadcastDate"])).toLocaleDateString());
        $(".series-description").html(await npo.translate(localStorage.getItem("language") || "EN", episode["description"]));
        $(".series-channel").attr("src", "img/" + episode["channel"] + ".svg");

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
        let h = Math.round(videoPlayer.videoHeight / 4);
        canvas.width = w;
        canvas.height = h;

        let context = canvas.getContext("2d");
        context.drawImage(videoPlayer, 0, h * 3, w, h, 0, 0, w, h);
        let image = canvas.toDataURL("image/png");

        $("#ocr").attr("src", image);

        image = image.split(",")[1];
        let response = await npo.ocr(image);
        if (response != null && this.lastOcr !== response) {
            this.lastOcr = response;
            const track = this.getCurrentTrack();
            let translation = await npo.translate(track.language, response);
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
class Broadcaster {
    static async parseUrl(broadcaster, url) {
        let response = await (await fetch(`broadcaster.php?broadcaster=${broadcaster}&url=${url}`)).json();
        if (response.error) {
            throw Error(response.error);
        }
        return response;
        // switch (broadcaster) {
        //     case "ceskatelevize":
        //         return await this.ceskatelevize(response);
        //     default:
        //         throw Error("Could not parse URL");
        // }
    }

    // static async ceskatelevize(response) {
    //     let formData = new FormData();
    //     formData.append("playlist[0][type]", "episode");
    //     formData.append("playlist[0][id]", response.id);
    //     formData.append("playlist[0][startTime]", "");
    //     formData.append("playlist[0][stopTime]", "");
    //     formData.append("requestUrl", "/ivysilani/embed/iFramePlayer.php");
    //     formData.append("requestSource", "iVysilani");
    //     formData.append("type", "html");
    //
    //     const postData = new URLSearchParams();
    //     for (const pair of formData) {
    //         postData.append(pair[0], pair[1]);
    //     }
    //
    //     let data = await (await fetch("https://www.ceskatelevize.cz/ivysilani/ajax/get-client-playlist/", {
    //         method: "post",
    //         body: postData,
    //         headers: {
    //             //"x-addr": "127.0.0.1",
    //             "Content-Type": "application/x-www-form-urlencoded",
    //             "Referer": "https://www.ceskatelevize.cz/ivysilani/embed/iFramePlayer.php?hash=bc84be37667151d4cd8336faba4eaaf8deb3cf65&IDEC=219%20452%2080108/0110&channelID=1&width=100%25"
    //         }
    //     })).json();
    //     data = await (await fetch(data.url)).json();
    //     return {
    //         "subtitles": response.subtitles,
    //         "video": data.playlist[0].streamUrls.main
    //     }
    // }
}
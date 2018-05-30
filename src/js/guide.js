class guide {
    async getGuide(date) {
        let content = await npo.getJson("https://start-api.npo.nl/epg/" + date + "?type=tv");

        let guideContent = "";
        for(let j = 0; j < content["epg"].length; j++) {
            guideContent +=
                `<div class="col-1">
                <img src="` + content["epg"][j]["channel"]["images"]["original"]["formats"]["web"]["source"] + `" style="width: 100px;">
                </div>
                <div class="col-11">`;

            let schedule = content["epg"][j]["schedule"];
            let guideRowContent = "";

            for (let i = 0; i < schedule.length; i++) {
                let start = new Date(schedule[i]["startsAt"]);
                let end = new Date(schedule[i]["endsAt"]);

                let getPixelsStart = start.getMinutes() + (start.getHours() * 60);
                let getPixelsLength = ((end.getMinutes() + (end.getHours() * 60)) - getPixelsStart);


                getPixelsStart *= 15;
                getPixelsLength *= 15;

                //console.log(getPixelsStart);
                //console.log(getPixelsLength);

                guideRowContent +=
                    `<div class="guide-item" style="left: ` + getPixelsStart + `px; width: ` + getPixelsLength + `px">
                <span class="overflow">`;
                if(schedule[i]["program"]["onDemand"]) {
                    guideRowContent += `<a href="player.html?v=` + schedule[i]["program"]["id"] + `"><i class="fa fa-play-circle"></i></a> `;
                }
                guideRowContent += `<strong>` + schedule[i]["program"]["title"] + `</strong></span><br>
                <span class="text-muted overflow">` + this.padTimeLeft(start.getHours()) + `:` + this.padTimeLeft(start.getMinutes()) +
                    ` - ` + this.padTimeLeft(end.getHours()) + `:` + this.padTimeLeft(end.getMinutes()) + `</span>
            </div>`
            }

            guideContent += guideRowContent;
            guideContent += "</div>";
        }
        $(".guide").html(guideContent);
    }

    padTimeLeft(time) {
        return (time.toString().length === 1 ? "0" + time : time);
    }

    async load() {
        $("#date").change(event => {
           this.getGuide($("#date").val());
        });

        let date = new Date();
        let currentDate = date.getFullYear() + "-" + this.padTimeLeft(date.getMonth() + 1) + "-" + this.padTimeLeft(date.getDate());
        $("#date").val(currentDate);
        this.getGuide(currentDate);
    }
}
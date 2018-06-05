(() => {
    $(".language-button").click(e => {
        localStorage.setItem("language", $(e.target).data("lang"));

        alert("This only affects automatically translated texts at the moment")
    })
})();
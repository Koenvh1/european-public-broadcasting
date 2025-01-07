## No longer maintained (2025-01-07)
It was fun for when it worked, but I no longer have the energy to keep up-to-date with the constant updates by streaming platforms.

# European Public Broadcasting

European Public Broadcasting is an attempt to make European content available throughout Europe, 
by providing automatically translated subtitles for broadcasters around Europe. 
This means that you can have subtitles translated for for example Danish or Norwegian television, 
and watch Danish programmes with English, Dutch or Swahili subtitles. 

Available online on https://publicbroadcasting.eu

## Adding broadcasters
Do you want to help with this project, and add support for your own broadcasters? Great!
Take a look at `src/app/Broadcaster/Broadcaster.php`, and create a class in that folder that extends
the `Broadcaster` class (see one of the ARD, DR, NPO, etc. classes for an example). 
Lastly, add the class you created to the `Broadcaster` class's `getStreamInformation` function (in alphabetical order),
and create a pull request.

### TODO
* Create a testing framework
* Find a way to dynamically register new broadcasters
* Translate the 

<?php


namespace Koenvh\PublicBroadcasting;


class StreamInformation
{
    /** @var $language string */
    private $language;
    /** @var $videoUrl string */
    private $videoUrl;
    /** @var $captionUrl string */
    private $captionUrl;
    /** @var $drmData array */
    private $drmData;
    /** @var $title string */
    private $title;

    /**
     * StreamInformation constructor.
     * @param string $language The language of the subtitles, set to "auto" to automatically determine the language
     * @param string $videoUrl "
     * @param string $captionUrl
     * @param array|null $drmData
     * @param string $title
     */
    public function __construct(string $language, string $videoUrl, string $captionUrl, $drmData = null, $title = "")
    {
        $this->language = $language;
        $this->videoUrl = $videoUrl;
        $this->captionUrl = $captionUrl;
        $this->drmData = $drmData;
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @return string
     */
    public function getVideoUrl()
    {
        return $this->videoUrl;
    }

    /**
     * @param string $videoUrl
     */
    public function setVideoUrl($videoUrl)
    {
        $this->videoUrl = $videoUrl;
    }

    /**
     * @return string
     */
    public function getCaptionUrl()
    {
        return $this->captionUrl;
    }

    /**
     * @param string $captionUrl
     */
    public function setCaptionUrl($captionUrl)
    {
        $this->captionUrl = $captionUrl;
    }

    /**
     * @return array
     */
    public function getDrmData()
    {
        return $this->drmData;
    }

    /**
     * @param array $drmData
     */
    public function setDrmData($drmData)
    {
        $this->drmData = $drmData;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }


}

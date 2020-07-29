<?php


namespace Koenvh\PublicBroadcasting;


class StreamInformation
{
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
     * @param string $videoUrl
     * @param string $captionUrl
     * @param array $drmData
     * @param string $title
     */
    public function __construct($videoUrl, $captionUrl, $drmData = null, $title = "")
    {
        $this->videoUrl = $videoUrl;
        $this->captionUrl = $captionUrl;
        $this->drmData = $drmData;
        $this->title = $title;
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

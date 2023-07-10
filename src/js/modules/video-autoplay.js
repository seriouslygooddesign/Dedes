const VideoAutoplay = () => {
  const videoSources = document.querySelectorAll("source");
  if (videoSources.length) {
    videoSources.forEach((source) => {
      const dataSrc = source.dataset.src;
      if (dataSrc) {
        source.src = source.dataset.src;
        source.parentElement.load();
      }
    });
  }
};

export default VideoAutoplay;

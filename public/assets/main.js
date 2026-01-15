(function () {
  const images = document.querySelectorAll('img[data-fallback]');
  images.forEach((img) => {
    img.addEventListener('error', () => {
      img.src = img.dataset.fallback;
    });
  });
})();

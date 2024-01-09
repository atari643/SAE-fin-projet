const series = document.getElementsByClassName('showChildSeries');
const seriesPoster = document.getElementsByClassName('posters');
for (let i = 0; i < series.length; i++) {
    seriesPoster[i].addEventListener('mouseover', function () {
        posterHover(i);
    });
    seriesPoster[i].addEventListener('mouseleave', function () {
        const overlay = series[i].querySelector('.overlay');
        if (overlay) {
            overlay.parentNode.removeChild(overlay);
        }
    });
    seriesPoster[i].addEventListener('click', function () {
        makeBannerLeft(series[i], seriesPoster[i]);
    });
}

function posterHover(i) {
    const overlay = document.createElement('div');
    overlay.classList.add('overlay');
    series[i].appendChild(overlay);
    const title = document.createElement('p');
    title.classList.add('titlePosterHover');
    title.innerHTML = series[i].dataset.title;
    overlay.appendChild(title);
    const duration = document.createElement('p');
    duration.classList.add('durationPosterHover');
    if (series[i].dataset.duration != null) {
        duration.innerHTML = series[i].dataset.duration;
    } else if (series[i].dataset.épisodes != null) {
        duration.innerHTML = series[i].dataset.épisodes;
    } else {
        duration.innerHTML = 'Pas de durée estimée';
    }
    overlay.appendChild(duration);
    const parentStars = document.createElement('div');
    parentStars.classList.add('parentStarsPosterHover');
    for (let j = 0; j < series[i].dataset.avis; j++) {
        parentStars.innerHTML += '<span class="material-symbols-outlined">grade</span>';
    }
    for (let j = 0; j < 5 - series[i].dataset.avis; j++) {
        parentStars.innerHTML += '<span class="material-symbols-outlined">grade</span>';
        parentStars.lastChild.style.color = '#e3f7f5';
    }
    overlay.appendChild(parentStars);
    overlay.style.width = seriesPoster[i].width + 'px';
    overlay.style.transform = 'translateY(' + (seriesPoster[i].offsetHeight - overlay.offsetHeight + Math.round(overlay.offsetHeight * 0.08)) + 'px) translateX(' + (seriesPoster[i].offsetLeft - overlay.offsetLeft + 1) + 'px) scale(1.08)';
}

function makeBannerLeft(poster, posterImg) {
    const banner = document.createElement('div');
    banner.classList.add('banner');
    const bannerTitle = document.createElement('p');
    const bannerClose = document.createElement('div');
    bannerClose.classList.add('bannerClose');
    bannerClose.innerHTML = '<span class="material-symbols-outlined">cancel</span>';
    banner.appendChild(bannerClose);
    bannerTitle.classList.add('bannerTitle');
    bannerTitle.innerHTML = poster.dataset.title;
    banner.appendChild(bannerTitle);
    const bannerImg = document.createElement('img');
    bannerImg.src = posterImg.src;
    banner.appendChild(bannerImg);
    const resumé = document.createElement('p');
    resumé.classList.add('resumé');
    resumé.innerHTML = poster.dataset.resum;
    banner.appendChild(resumé);
    const bannerDuration = document.createElement('p');
    bannerDuration.classList.add('bannerDuration');
    const parentBottomBanner = document.createElement('div');
    parentBottomBanner.classList.add('parentBottomBanner');
    banner.appendChild(parentBottomBanner);
    if (poster.dataset.duration != null) {
        bannerDuration.innerHTML = poster.dataset.duration;
    } else if (poster.dataset.épisodes != null) {
        bannerDuration.innerHTML = poster.dataset.épisodes;
    } else {
        bannerDuration.innerHTML = 'Pas de durée estimée';
    }
    parentBottomBanner.appendChild(bannerDuration);
    const bannerParentStars = document.createElement('div');
    bannerParentStars.classList.add('bannerParentStars');
    for (let j = 0; j < poster.dataset.avis; j++) {
        bannerParentStars.innerHTML += '<span class="material-symbols-outlined">grade</span>';
    }
    for (let j = 0; j < 5 - poster.dataset.avis; j++) {
        bannerParentStars.innerHTML += '<span class="material-symbols-outlined">grade</span>';
        bannerParentStars.lastChild.style.color = '#e3f7f5';
    }
    parentBottomBanner.appendChild(bannerParentStars);
    const suivieButton = document.createElement('div');
    suivieButton.classList.add('suivieButtonadd');suivieButton.innerHTML = '<span class="material-symbols-outlined">=heart_plus</span>';
    parentBottomBanner.appendChild(suivieButton);
}
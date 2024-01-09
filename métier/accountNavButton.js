const accountButton = document.querySelector('#accountButton');
accountButton.addEventListener('click', function () {
    if (document.querySelector('.déconnecter')) {
        document.querySelector('.déconnecter').parentNode.removeChild(document.querySelector('.déconnecter'));
    }else{
        accountButton.parentNode.insertBefore(makeAccountNav(), accountButton);
    }
});

function makeAccountNav() {
    const accountNav = document.createElement('div');
    accountNav.classList.add('déconnecter');
    accountNav.innerHTML = '<span class="material-symbols-outlined">logout</span> Se déconnecter';
    return accountNav;
}

//app.user sur twig vérifier l'utlisateur connecté
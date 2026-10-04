/* Interactions légères de la boutique (galerie, panier, filtres) */
(function () {
  'use strict';

  // Galerie de la fiche produit : sélection de la vignette
  document.addEventListener('click', function (event) {
    var thumb = event.target.closest('.thumb-row img');
    if (!thumb) return;
    var main = document.getElementById('product-main-image');
    if (!main) return;
    main.src = thumb.dataset.full || thumb.src;
    document.querySelectorAll('.thumb-row img').forEach(function (el) {
      el.classList.remove('active');
    });
    thumb.classList.add('active');
  });

  // Aperçu de l'image au survol des vignettes
  document.querySelectorAll('.thumb-row img').forEach(function (thumb) {
    var main = document.getElementById('product-main-image');
    if (!main) return;
    thumb.addEventListener('mouseenter', function () {
      main.src = thumb.dataset.full || thumb.src;
    });
  });

  // Panier : champ quantité
  document.querySelectorAll('[data-qty-input]').forEach(function (input) {
    input.addEventListener('change', function () {
      var form = input.closest('form');
      if (form) form.submit();
    });
  });

  // Filtres du catalogue : soumission automatique
  document.querySelectorAll('[data-auto-submit]').forEach(function (field) {
    field.addEventListener('change', function () {
      var form = field.closest('form');
      if (form) form.submit();
    });
  });

  // Bouton favori (formulaire POST)
  document.querySelectorAll('[data-wishlist]').forEach(function (button) {
    button.addEventListener('submit', function (event) {
      if (!window.confirm('Vous devez être connecté pour gérer vos favoris. Continuer ?')) {
        event.preventDefault();
      }
    });
  });

  // Notifications : masquage automatique
  var alerts = document.querySelectorAll('.alert[data-auto-dismiss]');
  alerts.forEach(function (alert) {
    setTimeout(function () {
      if (window.bootstrap && bootstrap.Alert) {
        bootstrap.Alert.getOrCreateInstance(alert).close();
      } else {
        alert.remove();
      }
    }, 6000);
  });
})();
<div class="tab-pane fade" id="magix-eventreg-pane" role="tabpanel" aria-labelledby="magix-eventreg-tab" tabindex="0">
    <div class="card shadow-sm border-0 mt-3">
        <div class="card-body">

            {* ==========================================
               PARTIE 1 : LA CONFIGURATION DE L'ÉVÈNEMENT
               ========================================== *}
            <form id="eventreg_config_form" class="mb-5 pb-4 border-bottom">
                <input type="hidden" name="hashtoken" value="{$hashtoken}">
                <input type="hidden" name="id_news" value="{$id_news}">

                {*  CORRECTION : On retire align-items-end pour aligner par le haut *}
                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label fw-bold text-primary">
                            <i class="bi bi-ui-checks me-1"></i> Formulaire d'inscription
                        </label>
                        <div class="form-check form-switch fs-5 mt-1">
                            <input class="form-check-input" type="checkbox" role="switch" id="registration_enabled" name="registration_enabled" value="1" {if ($event_config.registration_enabled|default:0) == 1}checked{/if}>
                            <label class="form-check-label fs-6 text-muted" for="registration_enabled">Ouvrir les inscriptions</label>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-bold text-primary">
                            <i class="bi bi-people me-1"></i> Quota
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">Max participants</span>
                            <input type="number" class="form-control" name="max_participants" value="{$event_config.max_participants|default:0}" min="0">
                        </div>
                        {* Le texte coule naturellement en dessous sans casser l'alignement *}
                        <div class="form-text">Mettez 0 si les places sont illimitées.</div>
                    </div>

                    <div class="col-md-3">
                        {*  CORRECTION : Espace vide pour pousser le bouton au niveau des inputs *}
                        <label class="form-label d-none d-md-block">&nbsp;</label>
                        <button type="button" class="btn btn-success w-100" onclick="saveEventConfig()">
                            <i class="bi bi-save me-2"></i> Enregistrer
                        </button>
                    </div>
                </div>
            </form>

            {* ==========================================
               PARTIE 2 : LA LISTE DES INSCRITS (AJAX)
               ========================================== *}
            <div id="magix-eventreg-app" data-module="news" data-id="{$id_news}">
                <div class="text-center py-5 text-muted">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <p>Chargement des participants...</p>
                </div>
            </div>

        </div>
    </div>
</div>

{block name="javascripts" append}
    <script>
        // 1. Sauvegarde de la configuration (Quota / On-Off)
        // On garde cette fonction à part car elle gère la config globale, pas la liste.
        function saveEventConfig() {
            const form = document.getElementById('eventreg_config_form');
            const formData = new FormData(form);

            fetch('index.php?controller=EventRegistration&action=saveConfig', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.status && typeof MagixToast !== 'undefined') {
                        MagixToast.success(data.message);
                    } else if (typeof MagixToast !== 'undefined') {
                        MagixToast.error(data.message);
                    }
                })
                .catch(err => console.error(err));
        }

        // 2. Initialisation de l'App AJAX pour la liste des inscrits
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof MagixAjaxManager !== 'undefined') {
                //  UTILISATION DE VOTRE GESTIONNAIRE GLOBAL
                window.eventRegApp = new MagixAjaxManager(
                    'magix-eventreg-app',    // ID du conteneur
                    'magix-eventreg-tab',    // ID de l'onglet
                    'EventRegistration',     // Nom du contrôleur PHP
                    'eventreg',              // Préfixe générique
                    'registration'           // ID Key (id_registration)
                );
            }
        });
    </script>
{/block}
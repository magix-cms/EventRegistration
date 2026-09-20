<div class="card shadow-sm border-0 bg-light mt-5 mb-4" id="event-registration-block">
    <div class="card-body p-4 p-md-5">
        <h3 class="h4 fw-bold text-primary mb-3">
            <i class="bi bi-ticket-perforated me-2"></i> Inscription à l'évènement
        </h3>

        {if $event_is_full}
            <div class="alert alert-warning border-0 d-flex align-items-center mt-4">
                <i class="bi bi-exclamation-triangle-fill fs-3 me-3"></i>
                <div>
                    <h5 class="mb-1">Évènement complet !</h5>
                    <p class="mb-0">Désolé, toutes les places pour cet évènement ont été réservées.</p>
                </div>
            </div>
        {else}
            <div id="event-form-wrapper">
                <p class="text-muted mb-4">
                    Veuillez remplir ce formulaire pour valider votre participation.
                    {if $event_places !== 'illimité'}
                        <span class="badge bg-info text-dark ms-2">
                            Il reste <span id="event-places-count" class="fw-bold fs-6">{$event_places}</span> place(s)
                        </span>
                    {/if}
                </p>

                {*  On utilise validate_form pour que votre classe globale s'occupe de tout *}
                <form id="eventRegForm" class="validate_form" method="post" action="{$base_url}{$current_lang.iso_lang}/Eventregistration/registerfrontend">
                    <input type="hidden" name="hashtoken" value="{$event_token}">
                    <input type="hidden" name="id_news" value="{$event_id_news}">

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="form-group mb-3">
                                <label for="reg_firstname" class="form-label fw-bold">Prénom <span class="text-danger">*</span></label>
                                <input id="reg_firstname" type="text" name="firstname" class="form-control" required/>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-group mb-3">
                                <label for="reg_lastname" class="form-label fw-bold">Nom <span class="text-danger">*</span></label>
                                <input id="reg_lastname" type="text" name="lastname" class="form-control" required/>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="form-group mb-3">
                                <label for="reg_email" class="form-label fw-bold">Adresse E-mail <span class="text-danger">*</span></label>
                                <input id="reg_email" type="email" name="email" class="form-control" required/>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-group mb-3">
                                <label for="reg_phone" class="form-label fw-bold">Téléphone <span class="text-muted small">(Optionnel)</span></label>
                                <input id="reg_phone" type="tel" name="phone" class="form-control"/>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4 py-2">
                            <i class="bi bi-check-circle me-2"></i> Confirmer mon inscription
                        </button>
                    </div>
                </form>
            </div>
        {/if}
    </div>
</div>

{if !$event_is_full}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('eventRegForm');

            if (form) {
                //  On écoute l'événement générique MagixFrontForms
                form.addEventListener('magix:form:success', function(e) {
                    const data = e.detail; // Contient la réponse JSON du serveur

                    //  CORRECTION : On cherche directement data.remaining
                    if (data.remaining !== undefined && data.remaining !== 'illimité') {
                        const remaining = data.remaining;
                        const countEl = document.getElementById('event-places-count');

                        if (countEl) {
                            countEl.innerText = remaining;
                        }

                        // Verrouillage total si c'est la dernière place
                        if (remaining === 0) {
                            const wrapper = document.getElementById('event-form-wrapper');
                            wrapper.innerHTML = `
                            <div class="alert alert-warning border-0 d-flex align-items-center mt-4 fade show">
                                <i class="bi bi-exclamation-triangle-fill fs-3 me-3"></i>
                                <div>
                                    <h5 class="mb-1">Évènement complet !</h5>
                                    <p class="mb-0">Félicitations, vous avez pris la toute dernière place pour cet évènement !</p>
                                </div>
                            </div>
                            `;
                        }
                    }
                });
            }
        });
    </script>
{/if}
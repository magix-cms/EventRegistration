{* Champ caché de sécurité *}
<input type="hidden" id="eventreg_hashtoken" value="{$hashtoken}">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 text-gray-800"><i class="bi bi-list-check me-2"></i>Liste des participants</h5>
    <span class="badge bg-primary fs-6">Total : {$registrations|count}</span>
</div>

{* Injection dans le template core *}
{include file="components/ajax-table.tpl"
data=$registrations
id_key="id_registration"
columns=$ajax_columns
sortable=false
delete_action="eventRegApp.deleteItem"
empty_msg="Aucun participant ne s'est encore inscrit à cet évènement."
}
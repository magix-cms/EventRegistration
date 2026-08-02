{extends file="emails/layout.tpl"}

{block name="email_content"}
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td style="padding: 20px; font-family: sans-serif; font-size: 16px; line-height: 24px; color: #333333;">

                {* Titre principal *}
                <h2 style="margin-top: 0; color: {$main_color|default:'#0d6efd'};">
                    {$title|default:'Notification'}
                </h2>

                {* Introduction éventuelle *}
                {if isset($intro)}
                    <p>{$intro}</p>
                {/if}

                <hr style="border:none; border-top: 1px solid #EEEEEE; margin: 20px 0;">

                {if isset($subject)}
                    <p><strong>Sujet :</strong> {$subject}</p>
                {/if}

                {* AFFICHAGE ADMIN : Un beau tableau avec les détails *}
                {if isset($details) && is_array($details)}
                    <div style="background: #f8f9fa; border: 1px solid #dddddd; border-radius: 5px; padding: 15px; margin-top: 15px;">
                        <table width="100%" cellpadding="6" cellspacing="0" style="font-size: 15px; text-align: left;">
                            {foreach $details as $label => $val}
                                <tr>
                                    <td width="35%" style="color: #555555; border-bottom: 1px solid #eeeeee;"><strong>{$label}</strong></td>
                                    <td width="65%" style="border-bottom: 1px solid #eeeeee;">{$val}</td>
                                </tr>
                            {/foreach}
                        </table>
                    </div>

                    {* AFFICHAGE UTILISATEUR : Un texte formaté *}
                {elseif isset($content)}
                    <div style="background: #f8f9fa; border-left: 4px solid {$main_color|default:'#0d6efd'}; padding: 15px;">
                        {$content nofilter}
                    </div>
                {/if}

            </td>
        </tr>
    </table>
{/block}
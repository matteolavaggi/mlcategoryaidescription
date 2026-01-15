{*
 * 2010-2026 2win.agency
 *
 * NOTICE OF LICENSE
 *
 * This file is not open source! Each license that you purchased is only available for 1 wesite only.
 * If you want to use this file on more websites (or projects), you need to purchase additional licenses.
 * You are not allowed to redistribute, resell, lease, license, sub-license or offer our resources to any third party.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please contact us for extra customization service at an affordable price
 *
 * @author    2win.agency
 * @copyright 2010-2026 2win.agency
 * @license   Valid for 1 website (or project) for each purchase of license
 *            International Registered Trademark & Property of 2win.agency
 *}

<div class="panel" id="module-header-info">
    <div class="row">
        <div class="col-lg-12">
            <div class="module-header-content" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                
                {* Module Title & Description *}
                <div class="module-info" style="flex: 1; min-width: 300px;">
                    <h2 style="margin: 0 0 5px 0; color: #363a41;">
                        <i class="icon-cogs"></i> {$module_display_name|escape:'htmlall':'UTF-8'}
                    </h2>
                    {if isset($module_description) && $module_description}
                        <p style="margin: 0; color: #666; font-size: 13px;">
                            {$module_description|escape:'htmlall':'UTF-8'}
                        </p>
                    {/if}
                </div>

                {* Quick Links *}
                <div class="module-links" style="display: flex; gap: 10px; flex-wrap: wrap;">
                    
                    {* Documentation Link *}
                    {if isset($documentation_url) && $documentation_url}
                        <a href="{$documentation_url|escape:'htmlall':'UTF-8'}" 
                           target="_blank" 
                           class="btn btn-default"
                           title="{l s='Read Documentation' mod='mlcategoryaidescription'}">
                            <i class="icon-book"></i> {l s='Documentation' mod='mlcategoryaidescription'}
                        </a>
                    {/if}
                    
                    {* Support Link *}
                    {if isset($support_url) && $support_url}
                        <a href="{$support_url|escape:'htmlall':'UTF-8'}" 
                           target="_blank" 
                           class="btn btn-default"
                           title="{l s='Get Support' mod='mlcategoryaidescription'}">
                            <i class="icon-life-ring"></i> {l s='Support' mod='mlcategoryaidescription'}
                        </a>
                    {/if}
                    
                    {* Rate Link *}
                    {if isset($rate_url) && $rate_url}
                        <a href="{$rate_url|escape:'htmlall':'UTF-8'}" 
                           target="_blank" 
                           class="btn btn-default"
                           title="{l s='Rate this module' mod='mlcategoryaidescription'}">
                            <i class="icon-star"></i> {l s='Rate' mod='mlcategoryaidescription'}
                        </a>
                    {/if}

                </div>

            </div>
            
            {* Version Info *}
            {if isset($module_version) && $module_version}
                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                    <small style="color: #999;">
                        {l s='Version' mod='mlcategoryaidescription'}: {$module_version|escape:'htmlall':'UTF-8'}
                    </small>
                </div>
            {/if}
            
        </div>
    </div>
</div>

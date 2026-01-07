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
                           title="{l s='Read Documentation' mod='mlgooglenoindex'}">
                            <i class="icon-book"></i> {l s='Documentation' mod='mlgooglenoindex'}
                        </a>
                    {/if}

                    {* Support Link *}
                    {if isset($support_url) && $support_url}
                        <a href="{$support_url|escape:'htmlall':'UTF-8'}" 
                           target="_blank" 
                           class="btn btn-default"
                           title="{l s='Get Support' mod='mlgooglenoindex'}">
                            <i class="icon-life-ring"></i> {l s='Support' mod='mlgooglenoindex'}
                        </a>
                    {/if}

                    {* Rate Module Link (optional) *}
                    {if isset($rate_url) && $rate_url}
                        <a href="{$rate_url|escape:'htmlall':'UTF-8'}" 
                           target="_blank" 
                           class="btn btn-default"
                           title="{l s='Rate this module' mod='mlgooglenoindex'}">
                            <i class="icon-star"></i> {l s='Rate Us' mod='mlgooglenoindex'}
                        </a>
                    {/if}

                </div>
            </div>

            {* Credits Bar *}
            <div class="module-credits" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                
                <div class="credits-left" style="color: #999; font-size: 12px;">
                    <span>
                        {l s='Developed by' mod='mlgooglenoindex'} 
                        <a href="https://2win.agency" target="_blank" style="color: #25b9d7; text-decoration: none; font-weight: 600;">
                            2win.agency
                        </a>
                    </span>
                    {if isset($module_version) && $module_version}
                        <span style="margin-left: 15px; padding-left: 15px; border-left: 1px solid #ddd;">
                            {l s='Version' mod='mlgooglenoindex'}: {$module_version|escape:'htmlall':'UTF-8'}
                        </span>
                    {/if}
                </div>

                <div class="credits-right" style="font-size: 12px;">
                    <a href="https://addons.prestashop.com/en/2_community-developer?contributor=77754" 
                       target="_blank" 
                       style="color: #999; text-decoration: none;">
                        <i class="icon-shopping-cart"></i> {l s='More modules by 2win.agency' mod='mlgooglenoindex'}
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>

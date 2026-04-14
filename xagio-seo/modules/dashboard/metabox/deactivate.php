<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly
?>
<!-- New Project -->
<dialog id="xagio-deactivate-plugin" class="xagio-modal xagio-modal-sm">
    <div class="xagio-modal-header">
        <h3 class="xagio-modal-title"><i class="xagio-icon xagio-icon-warning"></i> Deactivate Xagio</h3>
        <button class="xagio-modal-close"><i class="xagio-icon xagio-icon-close"></i></button>
    </div>

    <div class="xagio-modal-body">
        <form id="xagio-deactivate">
            <input type="hidden" name="action" value="xagio_deactivate"/>

            <p class="xagio-margin-bottom-medium">
                Choose how your Xagio will be deactivated and what data will be preserved or removed.
            </p>

            <label class="xagio_remove_license xagio-display-block xagio-margin-bottom-small"><input type="checkbox" name="xagio_remove_license" value="1"
                                                       class="xagio-input-checkbox xagio-input-checkbox-mini">
                Disconnect Account from this Website
            </label>

            <label class="xagio_remove_data xagio-display-block xagio-margin-bottom-small"><input type="checkbox" name="xagio_remove_data" value="1"
                                                    class="xagio-input-checkbox xagio-input-checkbox-mini">
                Remove Data from this Website
            </label>

            <label class="xagio_remove_data_remote xagio-display-block xagio-margin-bottom-small"><input type="checkbox" name="xagio_remove_data_remote" value="1"
                                                           class="xagio-input-checkbox xagio-input-checkbox-mini">
                Disconnect Account & Remove from Dashboard
            </label>

            <div class="xagio-flex-center xagio-flex-gap-medium xagio-margin-top-medium">
                <button class="xagio-button xagio-button-outline" type="button" data-xagio-close-modal><i
                            class="xagio-icon xagio-icon-close"></i> Cancel
                </button>
                <button class="xagio-button xagio-button-primary" type="submit"><i
                            class="xagio-icon xagio-icon-check"></i> Deactivate
                </button>
            </div>
        </form>
    </div>
</dialog>
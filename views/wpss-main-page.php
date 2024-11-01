<?php


?>

<div class="wpss-css">
    <!-- <div>
        <input type="text" id="same_server_path_staging_wpss" val="">
    </div>
    <div>
        <input type="button" id="start_staging_wpss" value="Start Staging">
    </div> -->
    <h2 id='staging_area_wpss'>Super Stage WP</h2>
    <div id='staging_current_progress_wpss' style='display:none'>Checking status...</div>

    
    <div class="wpss_dialog_wrapper" style="display: none;">
        <div class="dialog-cont wpss_dialogs copy_live_to_staging_dialog_wpss" style="display: none;">
            <div class="dialog-icon-cont">
                <div class="dialog-icon live-stg"></div>
            </div>
            <div class="dialog-message">Copy from Live to Staging?</div>
            <div class="dialog-helptext">
                Are you sure you want to copy the live site to this staging site?
            </div>
            <div class="dialog-btn-cont">
                <button class="cancel">No, don't copy</button>
                <button class="confirm yes_copy_live_to_staging_dialog_wpss">Yes, copy to this staging</button>
            </div>
        </div>
        <div class="dialog-cont wpss_dialogs delete_staging_dialog_wpss">
            <div class="dialog-icon-cont">
                <div class="dialog-icon del"></div>
            </div>
            <div class="dialog-message">Delete staging?</div>
            <div class="dialog-helptext">
                Are you sure you want to delete this staging site?
            </div>
            <div class="dialog-btn-cont">
                <button class="cancel">No, don't delete</button>
                <button class="confirm red yes_delete_staging_dialog_wpss">Yes, delete this staging</button>
            </div>
        </div>
        <div class="dialog-cont wpss_dialogs stop_staging_dialog_wpss">
            <div class="dialog-icon-cont">
                <div class="dialog-icon del"></div>
            </div>
            <div class="dialog-message">Stop staging process?</div>
            <div class="dialog-helptext">
                Clicking on Yes will delete your current staging site. Are you sure want to continue?
            </div>
            <div class="dialog-btn-cont">
                <button class="cancel">No, don't delete</button>
                <button class="confirm red yes_stop_staging_dialog_wpss">Yes, delete this staging</button>
            </div>
        </div>
    </div>
    
    <?php add_thickbox(); ?>
</div>

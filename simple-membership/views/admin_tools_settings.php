
<div id="poststuff">
    <div id="post-body">

        <div class="postbox">
            <h3 class="hndle"><label for="title"><?php echo SwpmUtils::_('Generate a Registration Completion link') ?></label></h3>
            <div class="inside">

                <p><strong><?php echo SwpmUtils::_('You can manually generate a registration completion link here and give it to your customer if they have missed the email that was automatically sent out to them after the payment.') ?></strong></p>

                <form action="" method="post">
                    <table>
                        <tr>
                            <?php echo SwpmUtils::_('Generate Registration Completion Link') ?>
                        <br /><input type="radio" value="one" name="swpm_link_for" /><?php SwpmUtils::e('For a Particular Member ID'); ?>
                        <input type="text" name="member_id" size="5" value="" />
                        <br /><strong><?php echo SwpmUtils::_('OR') ?></strong>
                        <br /><input type="radio" checked="checked" value="all" name="swpm_link_for" /> <?php echo SwpmUtils::_('For All Incomplete Registrations') ?>
                        </tr>
                        <tr>
                            <td>
                                <div class="swpm-margin-top-10"></div>
                                <?php echo SwpmUtils::_('Send Registration Reminder Email Too') ?> <input type="checkbox" value="checked" name="swpm_reminder_email">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="swpm-margin-top-10"></div>
                                <input type="submit" name="submit" class="button-primary" value="<?php echo SwpmUtils::_('Submit') ?>" />
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <div class="swpm-margin-top-10"></div>
                                <?php
                                if (!empty($links)) {
                                    echo '<div class="swpm-green-box">' . SwpmUtils::_('Link(s) generated successfully. The following link(s) can be used to complete the registration.') . '</div>';
                                } else {
                                    echo '<div class="swpm-grey-box">' . SwpmUtils::_('Registration completion links will appear below') . '</div>';
                                }
                                ?>                                    
                                <div class="swpm-margin-top-10"></div>
                                <?php foreach ($links as $key => $link) { ?>
                                    <input type="text" size="120" readonly="readonly" name="link[<?php echo $key ?>]" value="<?php echo $link; ?>"/><br/>
                                <?php } ?>
                                    
                                <?php
                                if (isset($_REQUEST['swpm_reminder_email'])) {
                                    echo '<div class="swpm-green-box">' . SwpmUtils::_('A prompt to complete registration email was also sent.') . '</div>';
                                }
                                ?>
                            </td>
                        </tr>

                    </table>
                </form>

            </div></div>

    </div><!-- end of post-body -->
</div><!-- end of poststuff -->


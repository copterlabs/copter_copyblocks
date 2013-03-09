<?php

/**
 * Base methods for Copter Labs Plugins
 * 
 * @author Jason Lengstorf <jason.lengstorf@copterlabs.com>
 */
abstract class Copter_Plugin
{
    const PFX = 'copter_';
    public $wp_editor_config = array(
                                    'media_buttons' => FALSE,
                                    'textarea_rows' => 8,
                                    'teeny' => TRUE,
                                );

    protected function add_field( $id, $label = NULL, $type='text', $placeholder=NULL, $description=NULL )
    {
        $input_id = self::PFX . str_replace(self::PFX, '', $id);
        $func = $type==='textarea' ? '_textarea' : '_input';
        $func = $type==='checkbox' ? '_checkbox' : $func;
        if (!empty($description)) {
            $description ='<p><em>' . $description . '</em></p>';
        }
        ?>
                <tr>
                    <th scope="row" class="label-<?=$type?>">
                        <label for="<?=self::PFX, $id?>">
                            <?=$label?> 
                        </label>
                    </th>
                    <td><?php $this->$func($input_id, $type, $placeholder); echo $description; ?></td>
                </tr>
        <?php
    }

    protected function _input( $id, $type, $placeholder )
    {
        $value = htmlentities($this->get_value($id), ENT_QUOTES);
        ?> 
                        <input type="<?=$type?>"
                               class="input-<?=$type?>"
                               id="<?=$id?>" 
                               name="<?=$id?>" 
                               placeholder="<?=$placeholder?>"
                               value="<?=$value?>" />
                    <?php // Tabs here because I'm anal about code indentation
    }

    protected function _checkbox( $id, $type, $placeholder )
    {
        $value   = htmlentities($this->get_value($id), ENT_QUOTES);
        $checked = $value==1 ? ' checked' : NULL;
        $value   = 1;
        ?> 
                        <input type="<?=$type?>"
                               class="input-<?=$type?>"
                               id="<?=$id?>" 
                               name="<?=$id?>" 
                               value="<?=$value?>"
                               <?=$checked?> />
                    <?php // Tabs here because I'm anal about code indentation
    }

    protected function _textarea( $id, $type, $placeholder )
    {
        wp_editor($this->get_value($id), $id, $this->wp_editor_config);
    }

    abstract protected function get_value( $key );

    protected function pfx( $value ) {
        return self::PFX . str_replace(self::PFX, '', $value);
    }
}

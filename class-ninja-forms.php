<?php

namespace Ninja2Gravity;

use GFAPI;

class Ninja_Forms {

    // public function __construct() {
    //     Ninja_Forms()->plugins_loaded();
    // }

    public static function get_forms() {

        $forms = array();

        foreach ( Ninja_Forms()->form()->get_forms() as $form ) {
            $forms[] = Ninja_Forms()->form( $form->get_id() );
        }

        return $forms;
    }

    public static function nff_to_export( $file ) {

        if ( ! file_exists( $file ) ) {
            return false;
        }

        $nff = file_get_contents( $file );

        if ( empty( $nff ) ) {
            return false;
        }

        return json_decode( $nff, true );
    }

    public static function convert_to_gravity_form( $form_export ) {

        // $form_export = Ninja_Forms()->form( $form_id )->export_form( true );

        $gravity_form = array();

        $gravity_form['title'] = $form_export['settings']['title'];

        foreach ( $form_export['fields'] as $nf_field ) {

            $type = self::field_mapping( $nf_field['type'] );

            if ( empty( $type ) ) {
                continue;
            }

            $arguments = array(
                'type' => $type,
                'label' => $nf_field['label']
            );

            $gf_field = \GF_Fields::create( $arguments );

            $gravity_form['fields'][] = $gf_field;

        }

        $form_id = GFAPI::add_form( $gravity_form );

        if ( empty( $form_id ) ) {
            return false;
        }

        return $form_id;

    }

    public static function field_mapping( $nf_field = null ) {

        $mapping = array(
            'address' => 'address',
            'address2' => 'address',
            'button' => null,
            'checkbox' => 'checkbox',
            'city' => 'text',
            'confirm' => 'text',
            'date' => 'date',
            'email' => 'email',
            'firstname' => 'text',
            'html' => 'html',
            'hidden' => 'hidden',
            'lastname' => 'text',
            'listcheckbox' => 'checkbox',
            'listcountry' => 'select',
            'listimage' => null,
            'listmultiselect' => 'multiselect',
            'listradio' => null,
            'listselect' => 'select',
            'liststate' => 'select',
            'note' => 'textarea',
            'number' => 'number',
            'password' => 'password',
            'passwordconfirm' => 'password',
            'phone' => 'phone',
            'product' => 'product',
            'quantity' => 'quantity',
            'recaptcha' => 'captcha',
            'recaptchav3' => 'captcha',
            'repeater' => 'repeater',
            'shipping' => 'shipping',
            'spam' => null,
            'starrating' => null,
            'submit' => null,
            'terms' => null,
            'textarea' => 'textarea',
            'textbox' => 'text',
            'total' => 'total',
            'unknown' => null,
            'zip' => 'text',
            'hr' => 'section',
        );

        if ( empty( $nf_field ) ) {
            return $mapping;
        }

        if ( empty( $mapping[ $nf_field ] ) ) {
            return false;
        }

        return $mapping[ $nf_field ];

    }

}
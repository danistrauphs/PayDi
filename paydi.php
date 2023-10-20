<?php
/**
 * Plugin Name: PayDi
 * Description: Intégration de Payme (PG PAY) comme option de paiement dans WooCommerce.
 * Version: 1.0
 * Author: Daniel CHARLES
 */

add_action('plugins_loaded', 'init_payme_gateway');

function init_payme_gateway() {
    class WC_Payme_Gateway extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = 'payme';
            $this->has_fields = true; // Maintenant, nous avons des champs personnalisés
            $this->method_title = 'Payme';
            $this->method_description = 'Payer avec Payme';
            $this->title = 'Payme';
            $this->icon = ''; // URL de l'icône si vous en avez une

            $this->init_form_fields();
            $this->init_settings();

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => 'Activer/Désactiver',
                    'type' => 'checkbox',
                    'label' => 'Activer la passerelle de paiement Payme',
                    'default' => 'yes',
                ),
                'title' => array(
                    'title' => 'Titre',
                    'type' => 'text',
                    'description' => 'Le titre affiché pour cette méthode de paiement',
                    'default' => 'Payme',
                ),
                'payme_link' => array(
                    'title' => 'Lien Payme Personnel',
                    'type' => 'text',
                    'description' => 'Entrez votre lien Payme personnel',
                    'default' => '',
                ),
            );
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);

            // Obtenez le montant total de la commande
            $montant_commande = $order->get_total();

            // Obtenez le lien Payme personnel de l'utilisateur à partir des paramètres du plugin
            $payme_link = $this->get_option('payme_link');

            // Vérifiez si le lien Payme personnel a été saisi
            if (empty($payme_link)) {
                wc_add_notice('Veuillez entrer votre lien Payme personnel dans les paramètres du plugin.', 'error');
                return;
            }

            // Construisez le lien Payme avec le montant
            $url_payme = $payme_link . '?montant=' . $montant_commande;

            // Redirigez l'utilisateur vers le lien Payme personnalisé
            return array(
                'result' => 'success',
                'redirect' => $url_payme,
            );
        }
    }

    // Ajouter la classe de passerelle de paiement à WooCommerce
    function add_payme_gateway($methods) {
        $methods[] = 'WC_Payme_Gateway';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'add_payme_gateway');
}

// Ajouter un menu personnalisé pour la configuration du plugin
add_action('admin_menu', 'ajouter_menu_plugin');

function ajouter_menu_plugin() {
    add_menu_page(
        'Configuration de PayDi', // Titre de la page
        'PayDi', // Titre du menu
        'manage_options', // Capacité requise pour accéder
        'paydi-settings', // Identifiant unique de la page
        'afficher_page_configuration_paydi' // Fonction pour afficher la page
    );
}

function afficher_page_configuration_paydi() {
    // Récupérez les paramètres actuels depuis la base de données
    $payme_link = get_option('payme_link');

    // Obtenez la version du plugin depuis le fichier principal
    $plugin_data = get_file_data(__FILE__, array('Version' => 'Version'));
    $version = $plugin_data['Version'];

    // Informations de l'auteur
    $auteur = 'Daniel CHARLES';
    $email_auteur = 'danistrauphs@gmail.com';

    // Affichez le formulaire de configuration
    echo '<div class="wrap">';
    echo '<h2>Configuration de PayDi</h2>';
    echo '<form method="post" action="">';

    echo '<table class="form-table">';
    echo '<tr valign="top">';
    echo '<th scope="row">Lien Payme Personnel</th>';
    echo '<td>';
    echo '<input type="text" name="payme_link" value="' . esc_attr($payme_link) . '" size="50" />';
    echo '<p class="description">Entrez votre lien Payme personnel.</p>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';

    echo '<p class="submit">';
    echo '<input type="submit" name="submit" id="submit" class="button button-primary" value="Enregistrer les paramètres" />';
    echo '</p>';

    echo '</form>';
    
    // Affichez les informations de version, d'auteur et de contact
    echo '<p>Version du Plugin : ' . esc_html($version) . '</p>';
    echo '<p>Auteur : ' . esc_html($auteur) . '</p>';
    echo '<p>Contact : <a href="mailto:' . esc_attr($email_auteur) . '">' . esc_html($email_auteur) . '</a></p>';
    
    echo '</div>';

    // Traitement du formulaire
    if (isset($_POST['submit'])) {
        // Récupérez la valeur du champ de lien Payme
        $nouveau_payme_link = sanitize_text_field($_POST['payme_link']);

        // Enregistrez la nouvelle valeur dans la base de données
        update_option('payme_link', $nouveau_payme_link);

        // Affichez un message de confirmation
        echo '<div class="updated"><p>Paramètres enregistrés avec succès.</p></div>';
    }
}

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'             => 'Le champ :attribute doit être accepté.',
    'active_url'           => 'Le champ :attribute n\'est pas une URL valide.',
    'after'                => 'Le champ :attribute doit être une date après :date.',
    'after_or_equal'       => 'Le champ :attribute doit être une date postérieure ou égale à :date.',
    'alpha'                => 'Le champ :attribute ne peut contenir que des lettres.',
    'alpha_dash'           => 'Le champ :attribute ne peut contenir que des lettres, des chiffres et des tirets.',
    "ascii_only"           => "Le champ :attribute ne peut contenir que des lettres, des chiffres et des tirets.",
    'alpha_num'            => 'Le champ :attribute ne peut contenir que des lettres, des chiffres.',
    'array'                => 'Le champ :attribute doit être un tableau.',
    'before'               => 'Le champ :attribute doit être une date avant :date.',
    'before_or_equal'      => 'Le champ :attribute doit être une date antérieure ou égale à :date.',
    'between'              => [
        'numeric' => 'Le champ :attribute doit être entre :min et :max.',
        'file'    => 'Le champ :attribute doit être entre :min et :max kilo-octets.',
        'string'  => 'Le champ :attribute doit être entre :min et :max caractères.',
        'array'   => 'Le champ :attribute doit être entre :min et :max éléments.',
    ],
    'boolean'              => 'Le champ :attribute doit être vrai ou faux.',
    'confirmed'            => 'Le champ :attribute de la confirmation ne correspond pas.',
    'date'                 => 'Le champ :attribute de la date n\'est pas valide.',
    'date_format'          => 'Le champ :attribute ne correspond pas au format :format.',
    'different'            => 'Le champ :attribute et :other doit être différent.',
    'digits'               => 'Le champ :attribute doit être :digits chiffres.',
    'digits_between'       => 'Le champ :attribute doit être entre :min et :max chiffres.',
    'dimensions'           => 'Le champ :attribute a des dimensions d\'image non valides (:min_width x :min_height px).',
    'distinct'             => 'Le champ :attribute a une valeur en double.',
    'email'                => 'Le champ :attribute doit être une adresse e-mail valide.',
    'exists'               => 'Le champ sélectionné :attribute n\'est pas valide.',
    'file'                 => 'Le champ :attribute doit être un fichier.',
    'filled'               => 'Le champ :attribute doit avoir une valeur.',
    'gt'                   => [
        'numeric' => 'Le champ :attribute doit être supérieur à :value.',
        'file'    => 'Le champ :attribute doit être supérieur à :value kilo-octets.',
        'string'  => 'Le champ :attribute doit être supérieur à :value caractères.',
        'array'   => 'Le champ :attribute doit être supérieur à :value éléments.',
    ],
    'gte'                  => [
         'numeric' => 'Le champ :attribute doit être supérieur ou égal :value.',
        'file'    => 'Le champ :attribute doit être supérieur ou égal :value kilo-octets.',
        'string'  => 'Le champ :attribute doit être supérieur ou égal :value caractères.',
        'array'   => 'Le champ :attribute doit avoir :value éléments ou plus.',
    ],
     'image'                => 'Le champ :attribute doit être une image.',
    'in'                   => 'Le champ sélectionné :attribute n\'est pas valide.',
    'in_array'             => 'Le champ :attribute n\'existe pas dans :other.',
    'integer'              => 'Le champ :attribute doit être un entier.',
    'ip'                   => 'Le champ :attribute doit être une adresse IP valide.',
    'ipv4'                 => 'Le champ :attribute doit être une adresse IPv4 valide.',
    'ipv6'                 => 'Le champ :attribute doit être une adresse IPv6 valide.',
    'json'                 => 'Le champ :attribute doit être une chaîne JSON valide.',
    'lt'                   => [
         'numeric' => 'Le champ :attribute doit être inférieur à :value.',
        'file'    => 'Le champ :attribute doit être inférieur à :value kilo-octets.',
        'string'  => 'Le champ :attribute doit être inférieur à :value caractères.',
        'array'   => 'Le champ :attribute doit être inférieur à :value éléments.',
    ],
    'lte'                  => [
         'numeric' => 'Le champ :attribute doit être inférieur ou égal :value.',
        'file'    => 'Le champ :attribute doit être inférieur ou égal :value kilo-octets.',
        'string'  => 'Le champ :attribute doit être inférieur ou égal à :value caractères.',
        'array'   => 'Le champ :attribute ne doit pas avoir plus de :value éléments.',
    ],
    'max'                  => [
        'numeric' => 'Le champ :attribute doit être au moins :min.',
        'file'    => 'Le champ :attribute doit être au moins :min kilo-octets.',
        'string'  => 'Le champ :attribute doit être au moins :min caractères.',
        'array'   => 'Le champ :attribute doit être au moins :min éléments.',
    ],
    'mimes'                => 'The :attribute must be a file of type: :values.',
    'mimetypes'            => 'The :attribute must be a file of type: :values.',
    'min'                  => [
        'numeric' => 'The :attribute must be at least :min.',
        'file'    => 'The :attribute must be at least :min kilobytes.',
        'string'  => 'The :attribute must be at least :min characters.',
        'array'   => 'The :attribute must have at least :min items.',
    ],
     'not_in'               => 'Le champ :attribute sélectionné n\'est pas valide.',
    'not_regex'            => 'Le champ :attribute le format n\'est pas valide.',
    'numeric'              => 'Le champ :attribute doit être un nombre.',
    'present'              => 'Le champ :attribute le champ doit être présent.',
    'regex'                => 'Le champ :attribute le format n\'est pas valide.',
    'required'             => 'Le champ :attribute est requis.',
    'required_if'          => 'Le champ :attribute est obligatoire lorsque :other est :value.',
    'required_unless'      => 'Le champ :attribute est obligatoire sauf si :other est en :values.',
    'required_with'        => 'Le champ :attribute est obligatoire lorsque :values est présent.',
    'required_with_all'    => 'Le champ :attribute est obligatoire lorsque :values est présent.',
    'required_without'     => 'Le champ :attribute est obligatoire lorsque :values n\'est pas présent.',
    'required_without_all' => 'Le champ :attribute champ est obligatoire lorsqu\'aucun des :values sont présents.',
    'same'                 => 'Le champ :attribute et :other doit correspondre.',
    'size'                 => [
         'numeric' => 'Le champ :attribute cc :size.',
        'file'    => 'Le champ :attribute doit être :size kilo-octets.',
        'string'  => 'Le champ :attribute doit être :size caractères.',
        'array'   => 'Le champ :attribute doit contenir :size éléments.',
    ],
    'string'               => 'Le champ :attribute doit être une chaîne.',
    'timezone'             => 'Le champ :attribute doit être une zone valide.',
    'unique'               => 'Le champ :attribute a déjà été pris.',
    'uploaded'             => 'Le champ :attribute échec du téléchargement.',
    'url'                  => 'Le champ :attribute son format n\'est pas valide.',
    "account_not_confirmed" => "Votre compte n'est pas confirmé, veuillez vérifier boîte de reception et confirmer votre adresse email.",
  	"user_suspended"        => " Votre compte a été suspendu, veuillez nous contacter en cas d\'erreur",
  	"letters"              => "Le champ :attribute doit contenir au moins une lettre ou un chiffre",
    'video_url'          => 'URL invalide supporte uniquement Youtube et Vimeo.',
    'update_max_length' => 'La publication ne doit pas dépasser :max caractères.',
    'update_min_length' => 'La publication doit contenir au moins :min caractères.',
    'video_url_required'   => 'Le champ URL de la vidéo est obligatoire lorsque le contenu présenté est une vidéo.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */
'attributes' => [
    
    'agree_gdpr' => 'Je suis d\'accord avec le traitement des données personnelles',
      'agree_terms' => 'Je suis d\'accord avec les Termes et Conditions',
      'agree_terms_privacy' => 'J\'accepte les conditions générales et la politique de confidentialité',
  		'full_name' => 'Nom Complet',
      'name' => 'Nom',
  		'username'  => 'Nom d\'Utilisateur',
      'username_email' => 'Adresse E-mail',
  		'email'     => 'Email',
  		'password'  => 'Mot de Passe',
  		'password_confirmation' => 'Confirmation mot de passe',
  		'website'   => 'Site Internet',
  		'location' => 'Emplacement',
  		'countries_id' => 'Pays',
  		'twitter'   => 'Twitter',
  		'facebook'   => 'Facebook',
  		'google'   => 'Google',
  		'instagram'   => 'Instagram',
  		'comment' => 'Commentaire',
  		'title' => 'Titre',
  		'description' => 'Description',
      'old_password' => 'Ancien Mot de Passe',
      'new_password' => 'Nouveau Mot de Passe',
      'email_paypal' => 'Email PayPal',
      'email_paypal_confirmation' => 'E-mail de confirmation PayPal',
      'bank_details' => 'Coordonnées Bancaires',
      'video_url' => 'URL de la vidéo',
      'categories_id' => 'Catégorie',
      'story' => 'Histoire',
      'image' => 'Image',
      'avatar' => 'Avatar',
      'message' => 'Message',
      'profession' => 'Profession',
      'thumbnail' => 'La Vignette',
      'address' => 'Adresse',
      'city' => 'Ville',
      'zip' => 'Code Postal/ZIP',
      'payment_gateway' => 'Passerelle de Paiement',
      'payment_gateway_tip' => 'Passerelle de Paiement',
      'MAIL_FROM_ADDRESS' => 'E-mail sans réponse',
      'FILESYSTEM_DRIVER' => 'Disque',
      'price' => 'Prix',
      'amount' => 'Montant',
      'birthdate' => 'Date de naissance',
      'navbar_background_color' => 'Couleur d\'arrière-plan de la barre de navigation',
    	'navbar_text_color' => 'Couleur du texte de la barre de navigation',
    	'footer_background_color' => 'Couleur de fond du bas de page',
    	'footer_text_color' => 'Couleur du texte du pied de page',

  'AWS_ACCESS_KEY_ID' => 'Amazon Key', // Not necessary edit
      'AWS_SECRET_ACCESS_KEY' => 'Amazon Secret', // Not necessary edit
      'AWS_DEFAULT_REGION' => 'Amazon Region', // Not necessary edit
      'AWS_BUCKET' => 'Amazon Bucket', // Not necessary edit

      'DOS_ACCESS_KEY_ID' => 'DigitalOcean Key', // Not necessary edit
      'DOS_SECRET_ACCESS_KEY' => 'DigitalOcean Secret', // Not necessary edit
      'DOS_DEFAULT_REGION' => 'DigitalOcean Region', // Not necessary edit
      'DOS_BUCKET' => 'DigitalOcean Bucket', // Not necessary edit

      'WAS_ACCESS_KEY_ID' => 'Wasabi Key', // Not necessary edit
      'WAS_SECRET_ACCESS_KEY' => 'Wasabi Secret', // Not necessary edit
      'WAS_DEFAULT_REGION' => 'Wasabi Region', // Not necessary edit
      'WAS_BUCKET' => 'Wasabi Bucket', // Not necessary edit

 //===== v2.0
      'BACKBLAZE_ACCOUNT_ID' => 'Backblaze Account ID', // Not necessary edit
      'BACKBLAZE_APP_KEY' => 'Backblaze Master Application Key', // Not necessary edit
      'BACKBLAZE_BUCKET' => 'Backblaze Bucket Name', // Not necessary edit
      'BACKBLAZE_BUCKET_REGION' => 'Backblaze Bucket Region', // Not necessary edit
      'BACKBLAZE_BUCKET_ID' => 'Backblaze Bucket Endpoint', // Not necessary edit

      'VULTR_ACCESS_KEY' => 'Vultr Key', // Not necessary edit
      'VULTR_SECRET_KEY' => 'Vultr Secret', // Not necessary edit
      'VULTR_REGION' => 'Vultr Region', // Not necessary edit
      'VULTR_BUCKET' => 'Vultr Bucket', // Not necessary edit
  	],

];
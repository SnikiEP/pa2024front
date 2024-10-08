<!DOCTYPE html>
<html lang="en">

<head>
    <?php
        $title = "Contact us - ATD";
        include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/head.php');
    ?>
    <script src="/assets/js/translation.js"></script>
    <style>
        main {
            padding: 20px;
            background-color: #2c2c2c;
            border-radius: 8px;
            margin: 20px auto;
            max-width: 800px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .content {
            font-family: Arial, sans-serif;
            color: #e0e0e0;
        }

        h1, h2 {
            color: #f0f0f0;
        }

        section {
            margin-bottom: 20px;
        }

        p {
            line-height: 1.6;
        }

        form {
            margin-top: 10px;
        }

        form div {
            margin-bottom: 10px;
        }

        label {
            display: block;
            color: #f0f0f0; 
        }

        input[type="text"], input[type="email"], textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #444;
            border-radius: 4px;
            background-color: #333; 
            color: #e0e0e0;
        }

        button {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            background-color: #0056b3;
            color: #ffffff;
            cursor: pointer;
        }

        button:hover {
            background-color: #004494;
        }

        a {
            color: #1e90ff; 
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php') ?>
        <main>
            <div class="content">
                <h1 data-translate="contact_title">Contact Us - Au Temps Donné</h1>
                
                <section>
                    <p data-translate="contact_intro">Nous serions ravis de vous entendre. Que vous ayez des questions sur nos services, que vous souhaitiez devenir bénévole, ou que vous ayez besoin d'assistance, n'hésitez pas à nous contacter par l'un des moyens ci-dessous.</p>
                </section>

                <section>
                    <h2 data-translate="contact_details">Coordonnées</h2>
                    <p><strong data-translate="address">Adresse :</strong> 123 Rue de la Solidarité, 75000 Paris, France</p>
                    <p><strong data-translate="phone">Téléphone :</strong> +33 1 23 45 67 89</p>
                    <p><strong data-translate="email">Email :</strong> contact@autempsdonne.fr</p>
                </section>

                <section>
                    <h2 data-translate="contact_form">Formulaire de Contact</h2>
                    <form action="/contact_form_handler.php" method="POST">
                        <div>
                            <label for="name" data-translate="name">Nom :</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div>
                            <label for="email" data-translate="email">Email :</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div>
                            <label for="subject" data-translate="subject">Sujet :</label>
                            <input type="text" id="subject" name="subject" required>
                        </div>
                        <div>
                            <label for="message" data-translate="message">Message :</label>
                            <textarea id="message" name="message" rows="5" required></textarea>
                        </div>
                        <div>
                            <button type="submit" data-translate="send">Envoyer</button>
                        </div>
                    </form>
                </section>

                <section>
                    <h2 data-translate="social_media">Réseaux Sociaux</h2>
                    <p data-translate="follow_us">Suivez-nous sur les réseaux sociaux pour rester informé de nos dernières nouvelles et événements :</p>
                    <ul>
                        <li><a href="https://www.facebook.com/ATD">Facebook</a></li>
                        <li><a href="https://www.twitter.com/ATD">Twitter</a></li>
                        <li><a href="https://www.instagram.com/ATD">Instagram</a></li>
                    </ul>
                </section>

                <section>
                    <h2 data-translate="office_hours">Nos Horaires</h2>
                    <p data-translate="office_schedule">Nos bureaux sont ouverts aux horaires suivants :</p>
                    <ul>
                        <li data-translate="mon_fri">Lundi - Vendredi : 9h00 - 18h00</li>
                        <li data-translate="saturday">Samedi : 10h00 - 14h00</li>
                        <li data-translate="sunday">Dimanche : Fermé</li>
                    </ul>
                </section>

            </div>
        </main>
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php')?>
    </div>
</body>
</html>

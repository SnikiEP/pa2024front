<footer class="footer">
  <div class="content has-text-centered">
      <p data-translate="footer_text">&copy; 2024-<?= date("Y"), ($translations['footer_text']) ?></p>
      <img src="<?= '/assets/img/ico-cg.png' ?>" alt="callidos-footer-logo" width="45px">
      <a class="navbar-item" data-translate="about_us" href="/public/about.php">About us</a>
      <a class="navbar-item" data-translate="contact_us" href="/public/contact.php">Contact us</a>
  </div>

  <style>
    footer {
        background-color: #f8f9fa;
        border-top: 1px solid #ddd;
        padding: 1rem 0;
        max-height: 600px;
        font-size: 0.875rem;
        text-align: center;
        box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
    }

    .footer .content {
        display: flex;
        flex-direction: column;
        align-items: center;
        max-width: 1200px;
        margin: 0 auto;
    }

    .footer p {
        margin: 0.5rem 0;
        color: #333;
    }

    .footer img {
        margin: 0.5rem 0;
        max-height: 45px;
    }

    .footer a.navbar-item {
        display: inline-block;
        margin: 0.25rem 0;
        color: #007bff;
        text-decoration: none;
        font-size: 0.875rem;
        transition: color 0.3s, text-decoration 0.3s;
    }

    .footer a.navbar-item:hover {
        text-decoration: underline;
        color: #0056b3;
    }

    @media (max-width: 768px) {
        .footer {
            padding: 0.5rem;
        }

        .footer .content {
            padding: 0 1rem;
        }
    }
  </style>
</footer>

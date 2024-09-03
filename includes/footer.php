<footer class="footer">
  <div class="content has-text-centered">
      <div class="footer-info">
          <img src="<?= '/assets/img/ico-cg.png' ?>" alt="callidos-footer-logo" width="45px">
          <p data-translate="footer_text">&copy; 2024-<?= date("Y"), ($translations['footer_text']) ?></p>
      </div>
      <div class="links">
          <a class="navbar-item" data-translate="about_us" href="/public/about.php">About us</a>
          <a class="navbar-item" data-translate="contact_us" href="/public/contact.php">Contact us</a>
      </div>
  </div>

  <style>
    footer {
        background-color: #f8f9fa;
        color: #f8f9fa;
        padding: 0.5rem 0;
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

    .footer-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .footer p {
        margin: 0;
        color: #f8f9fa;
    }

    .footer img {
        max-height: 45px;
    }

    .footer .links {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin: 0.25rem 0;
    }

    .footer a.navbar-item {
        color: #ffffff;
        text-decoration: none;
        font-size: 0.875rem;
        transition: color 0.3s, text-decoration 0.3s;
    }

    .footer a.navbar-item:hover {
        text-decoration: underline;
    }

    @media (max-width: 768px) {
        .footer {
            padding: 0.5rem;
        }

        .footer .content {
            flex-direction: column;
        }

        .footer .links {
            flex-direction: column;
            gap: 0.5rem;
        }

        .footer-info {
            flex-direction: column;
        }
    }
  </style>
</footer>

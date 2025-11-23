import React from "react";
import "../styles/Footer.css";
import { useSettings } from '../contexts/SettingsContext';

const Footer = () => {
  const { settings } = useSettings();
  return (
    <footer className="footer">
      <div className="footer-container">
        {/* Logo / App Name */}
        <div className="footer-logo">
          {settings?.app_name || 'FitNes'}<span className="footer-logo-accent">+</span>
        </div>

        {/* Footer Navigation */}
        <div className="footer-links">
          <a href="/">Home</a>
          <a href="/features">Features</a>
          <a href="/about">About</a>
          <a href="/contact">Contact</a>
        </div>

        {/* Socials */}
        <div className="footer-socials">
          <a href="#" title="Facebook"><i className="fab fa-facebook-f"></i></a>
          <a href="#" title="Instagram"><i className="fab fa-instagram"></i></a>
          <a href="#" title="Twitter"><i className="fab fa-twitter"></i></a>
        </div>
      </div>

      {/* Bottom section */}
      <div className="footer-bottom">
        <p>© {new Date().getFullYear()} {settings?.app_name || 'FitNes+'}. All rights reserved.</p>
        {settings?.support_email && <p>Support: <a href={`mailto:${settings.support_email}`}>{settings.support_email}</a></p>}
      </div>
    </footer>
  );
};

export default Footer;

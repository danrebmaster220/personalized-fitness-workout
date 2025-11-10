import React, { useState } from "react";
import "../styles/Home.css";
import FeatureCard from "../components/FeatureCard";
import Button from "../components/Button";

const Home = () => {
  const [loading, setLoading] = useState(false);

  const handleGenerateClick = () => {
    const token = localStorage.getItem("userToken");

    if (token) {
      // User logged in scroll to CTA section
      document
        .getElementById("generate-workout")
        ?.scrollIntoView({ behavior: "smooth" });
    } else {
      // Guest  redirect to login
      window.location.href = "/login";
    }
  };

  return (
    <div className="home-container">
      {/* HERO SECTION */}
      <section className="hero" id="hero">
        <div className="overlay"></div>
        <div className="hero-content">
          <h1>
            Sync Your Goals. Track Your Progress.
            <br />
            <span>Shape Your Results.</span>
          </h1>
          <p>
            Your personalized fitness and workout companion designed to help you
            reach your goals faster and smarter.
          </p>

          <Button text="Get Started" onClick={handleGenerateClick} loading={loading} />
        </div>
      </section>

      {/* FEATURES SECTION */}
      <section className="features" id="features">
        <h2>Features</h2>
        <p>
          Our platform uses intelligent APIs to generate a customized workout
          and meal plan tailored just for you.
        </p>

        <div className="feature-grid">
          <FeatureCard
            title="Personalized Workout Generator"
            description="Automatically creates a workout plan based on your age, weight, height, fitness level, and goals."
            icon="💪"
          />
          <FeatureCard
            title="Meal Recommendations"
            description="Get healthy meal suggestions to complement your fitness routine and goals."
            icon="🍎"
          />
          <FeatureCard
            title="Progress Tracker"
            description="Monitor your progress and see how far you’ve come on your fitness journey."
            icon="📊"
          />
          <FeatureCard
            title="PDF & History"
            description="Download your custom workout plan and view all your previously generated routines anytime."
            icon="📄"
          />
        </div>
      </section>

      {/* CTA SECTION */}
      <section className="cta-section" id="generate-workout">
        <h3>Are you ready to build your personalized workout plan?</h3>
        <Button
          text="Generate My Workout"
          onClick={handleGenerateClick}
          variant="secondary"
          loading={loading}
        />
      </section>

      {/* ABOUT SECTION */}
      <section id="about" className="about-container">
        <div className="about-header">
          <h1>About FitSync</h1>
          <p>Your personalized fitness companion for smarter workouts and faster results.</p>
        </div>

        <div className="about-section">
          <h2>Our Story</h2>
          <p>
            FitSync was created with a single purpose — to make fitness simple, smart, and personal.
            We understand that every journey is unique, so we built a platform that adapts to your
            goals, schedule, and progress. Whether you're a beginner or an athlete, FitSync evolves
            with you — syncing your workouts, nutrition, and performance into one seamless experience.
          </p>
        </div>

        <div className="about-section">
          <h2>Our Mission</h2>
          <p>
            To empower individuals to reach their full potential through personalized and data-driven
            fitness plans. FitSync merges technology and motivation to make achieving your health goals
            efficient, enjoyable, and sustainable.
          </p>
        </div>

        <div className="about-section">
          <h2>Our Vision</h2>
          <p>
            We envision a world where fitness is not just a routine, but a lifestyle —
            accessible, adaptable, and inspiring for everyone, everywhere.
          </p>
        </div>
      </section>

      {/* CONTACT SECTION */}
      <section id="contact" className="contact-container">
        <header>
          <h1>Contact Us</h1>
          <p>
            We’d love to hear from you! Whether you have questions, feedback, or collaboration ideas,
            reach out anytime.
          </p>
        </header>

        <main>
          <section>
            <p>
              <strong>Email:</strong>{" "}
              <a href="mailto:fitsync@gmail.com">fitsync@gmail.com</a>
            </p>
            <p>
              <strong>Phone:</strong> +63 951 470 2737
            </p>

            <h3>Follow Us</h3>
            <p>
              <a href="#">Facebook</a> | <a href="#">Instagram</a> | <a href="#">Twitter</a>
            </p>
          </section>

          <form action="#" method="post">
            <label htmlFor="firstname">First Name:</label>
            <input
              type="text"
              id="firstname"
              name="firstname"
              placeholder="Enter your first name"
              required
            />

            <label htmlFor="lastname">Last Name:</label>
            <input
              type="text"
              id="lastname"
              name="lastname"
              placeholder="Enter your last name"
              required
            />

            <label htmlFor="email">Email Address:</label>
            <input
              type="email"
              id="email"
              name="email"
              placeholder="Enter your email"
              required
            />

            <label htmlFor="message">Message:</label>
            <textarea
              id="message"
              name="message"
              rows="5"
              placeholder="Write your message..."
              required
            ></textarea>

            <button type="submit">Send Message</button>
          </form>
        </main>
      </section>

      {/* FOOTER */}
      <section className="footer">
        <div className="footer-container">
          {/* Logo */}
          <div className="footer-logo">
            FitSync<span className="footer-logo-accent"></span>
          </div>

          {/* Footer Navigation */}
          <div className="footer-links">
            <a href="#hero">Home</a>
            <a href="#features">Features</a>
            <a href="#about">About</a>
            <a href="#contact">Contact</a>
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
          <p>© {new Date().getFullYear()} FitSync. All rights reserved.</p>
        </div>
      </section>
    </div>
  );
};

export default Home;

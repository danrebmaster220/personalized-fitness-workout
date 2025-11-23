import React from 'react';

const GoogleLoginButton = () => {
  // Build the backend start URL using Vite env variable or fallback to Apache path.
  const API_BASE = import.meta.env.VITE_API_BASE || 'http://localhost/personalized-fitness-workout/backend/public';
  const authUrl = `${API_BASE.replace(/\/$/, '')}/google_auth.php`;

  return (
    <div style={{ marginTop: 12 }}>
      {/* anchor uses absolute URL so the browser performs a full navigation (not handled by React Router) */}
      <a href={authUrl} className="google-login-button" style={{display:'inline-block',padding:'10px 16px',background:'#4285F4',color:'#fff',borderRadius:6,textDecoration:'none'}}>
        Sign in with Google
      </a>
    </div>
  );
};

export default GoogleLoginButton;

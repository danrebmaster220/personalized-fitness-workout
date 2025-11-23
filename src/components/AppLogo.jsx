import React from 'react';
import '../styles/AppLogo.css';

// Render app name with an accent on the second segment (e.g., Fit<span>Sync</span>)
export default function AppLogo({ appName = 'FitSync', className = '' }) {
  // Try to split on space first, then on camel-case boundary (upper-case letter after first char)
  let left = appName;
  let right = null;

  if (appName.indexOf(' ') !== -1) {
    const parts = appName.split(' ');
    left = parts.slice(0, parts.length - 1).join(' ');
    right = parts[parts.length - 1];
  } else {
    // find first uppercase letter after the first char
    for (let i = 1; i < appName.length; i++) {
      const ch = appName.charAt(i);
      if (ch === ch.toUpperCase() && ch !== ch.toLowerCase()) {
        left = appName.slice(0, i);
        right = appName.slice(i);
        break;
      }
    }
  }

  return (
    <span className={`app-logo ${className || ''}`} aria-label={appName}>
      {left}
      {right ? <span className="accent">{right}</span> : null}
    </span>
  );
}

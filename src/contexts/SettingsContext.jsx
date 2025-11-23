import React, { createContext, useContext, useEffect, useState } from 'react';
import axios from 'axios';

const SettingsContext = createContext(null);

const API_BASE = import.meta.env.VITE_API_BASE || 'http://localhost/personalized-fitness-workout/backend/public';

export const SettingsProvider = ({ children }) => {
  const [settings, setSettings] = useState(null);
  const [loading, setLoading] = useState(true);

  const fetchSettings = async () => {
    setLoading(true);
    try {
      const res = await axios.get(`${API_BASE}/index.php?route=app&action=getPublicSettings`, { withCredentials: true });
      if (res.data?.success) {
        setSettings(res.data.settings || {});
      } else {
        setSettings({});
      }
    } catch (err) {
      console.error('Failed to load public settings:', err);
      setSettings({});
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchSettings();
  }, []);

  return (
    <SettingsContext.Provider value={{ settings: settings || {}, loading, refresh: fetchSettings }}>
      {children}
    </SettingsContext.Provider>
  );
};

export const useSettings = () => {
  return useContext(SettingsContext);
};

export default SettingsContext;

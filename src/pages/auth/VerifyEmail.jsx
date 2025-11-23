import React, { useEffect, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import axios from 'axios';

const API_BASE = '/api';

function useQuery() {
  return new URLSearchParams(useLocation().search);
}

export default function VerifyEmail() {
  const query = useQuery();
  const navigate = useNavigate();
  const token = query.get('token');

  const [status, setStatus] = useState('pending'); // pending, success, error
  const [msg, setMsg] = useState('Verifying your account...');

  useEffect(() => {
    if (!token) {
      setStatus('error');
      setMsg('Invalid verification token.');
      return;
    }

    const verify = async () => {
      try {
        const res = await axios.get(`${API_BASE}/index.php?route=user&action=verify&token=${encodeURIComponent(token)}`);
        if (res.data?.success) {
          setStatus('success');
          setMsg(res.data.message || 'Your account has been verified.');
        } else {
          setStatus('error');
          setMsg(res.data?.message || 'Verification failed.');
        }
      } catch (e) {
        console.error(e);
        setStatus('error');
        setMsg('Server error while verifying account.');
      }
    };

    verify();
  }, [token]);

  return (
    <div style={{ padding: 28 }}>
      <div style={{ maxWidth: 720, margin: '40px auto', background: '#fff', padding: 24, borderRadius: 10, boxShadow: '0 8px 30px rgba(0,0,0,0.08)' }}>
        <h2>Email Verification</h2>
        <p>{msg}</p>

        {status === 'success' ? (
          <div style={{ marginTop: 18 }}>
            <button onClick={() => navigate('/login')} className="btn-primary">Go to Login</button>
            <button onClick={() => navigate('/profile')} className="btn-secondary" style={{ marginLeft: 8 }}>Go to Profile</button>
          </div>
        ) : (
          status === 'error' && (
            <div style={{ marginTop: 18 }}>
              <button onClick={() => navigate('/register')} className="btn-secondary">Return</button>
            </div>
          )
        )}
      </div>
    </div>
  );
}

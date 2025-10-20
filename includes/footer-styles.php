<style>
.site-footer {
    background: rgba(255, 255, 255, 0.98);
    padding: 20px;
    margin-top: 60px;
    box-shadow: 0 -1px 3px rgba(0,0,0,0.05);
    backdrop-filter: blur(10px);
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.footer-left {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
    font-size: 14px;
}

.footer-brand {
    font-weight: 600;
    color: #667eea;
}

.footer-tagline {
    color: #999;
}

.footer-separator {
    color: #ddd;
}

.footer-right {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}

.footer-right a {
    color: #666;
    text-decoration: none;
    transition: color 0.3s;
}

.footer-right a:hover {
    color: #667eea;
}

.footer-copyright {
    text-align: center;
    color: #aaa;
    font-size: 12px;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
}

@media (max-width: 768px) {
    .footer-content {
        flex-direction: column;
        text-align: center;
        gap: 12px;
    }
    
    .footer-left,
    .footer-right {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>
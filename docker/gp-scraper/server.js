const express = require('express');
const gplay = require('google-play-scraper');

const app = express();
const PORT = 3001;

/**
 * GET /api/apps
 * Query params: collection, category, country, num
 * Returns array of apps from Google Play
 */
app.get('/api/apps', async (req, res) => {
    try {
        const {
            collection = 'topselling_new_free',
            category = 'APPLICATION',
            country = 'us',
            num = 50
        } = req.query;

        const results = await gplay.list({
            collection: gplay.collection[collection] || collection,
            category: gplay.category[category] || category,
            country,
            num: parseInt(num),
            fullDetail: false,
        });

        res.json(results);
    } catch (error) {
        console.error('Google Play scraper error:', error.message);
        res.status(500).json({ error: error.message });
    }
});

/**
 * GET /api/app/:appId
 * Returns full detail for a single app
 */
app.get('/api/app/:appId', async (req, res) => {
    try {
        const result = await gplay.app({ appId: req.params.appId });
        res.json(result);
    } catch (error) {
        console.error('Google Play app detail error:', error.message);
        res.status(500).json({ error: error.message });
    }
});

app.get('/health', (req, res) => res.json({ status: 'ok' }));

app.listen(PORT, '0.0.0.0', () => {
    console.log(`Google Play scraper running on port ${PORT}`);
});

import axios from 'axios';

const client = axios.create({
    baseURL: '/api/v1',
    timeout: 15000,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

export default client;

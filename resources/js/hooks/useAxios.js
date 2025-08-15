import axios from 'axios';
import { useEffect } from 'react';
import { createApp } from '@shopify/app-bridge';
import { getSessionToken } from '@shopify/app-bridge/utilities';
import { Redirect } from '@shopify/app-bridge/actions';

const useAxios = () => {
    const host =
        new URLSearchParams(window.location.search).get('host') ||
        document.querySelector('meta[name="shopify-host"]')?.getAttribute('content');

    if (!host) {
        document.getElementById('app').innerHTML = '<p>Loading failed: missing host parameter.</p>';
        throw new Error('Missing host parameter');
    }

    const app = createApp({
        apiKey: document.querySelector('meta[name="shopify-api-key"]').content,
        host: host || '', // Fallback to empty string just in case
    });

    useEffect(() => {
        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

        const requestInterceptor = axios.interceptors.request.use(
            async (config) => {
                const token = await getSessionToken(app);
                config.headers.Authorization = `Bearer ${token}`;
                config.params = {
                    ...config.params,
                    host,
                };
                return config;
            },
            (error) => Promise.reject(error)
        );

        const responseInterceptor = axios.interceptors.response.use(
            (response) => response,
            (error) => {
                if (error.response?.status === 403 && error.response.data?.forceRedirectUrl) {
                    const redirect = Redirect.create(app);
                    redirect.dispatch(Redirect.Action.REMOTE, error.response.data.forceRedirectUrl);
                }
                return Promise.reject(error);
            }
        );

        return () => {
            axios.interceptors.request.eject(requestInterceptor);
            axios.interceptors.response.eject(responseInterceptor);
        };
    }, [app, host]);

    return axios;
};

export default useAxios;

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import client from '../api/client';
import { weekday } from '../utils/dates';
import { useToast } from '../composables/useToast';
import { limits } from '../utils/limits';

function storedCity() {
    try {
        return localStorage.getItem('iqt.weather.city') || 'London';
    } catch {
        return 'London';
    }
}

const draft = ref(storedCity().slice(0, limits.city));
const { push } = useToast();

watch(draft, (value) => {
    if (value.length > limits.city) {
        draft.value = value.slice(0, limits.city);
    }
});

const report = ref(null);
const loading = ref(false);
const error = ref('');

const place = computed(() => {
    if (!report.value) {
        return '';
    }

    return [report.value.city, report.value.region, report.value.country].filter(Boolean).join(', ');
});

function formatTemp(value) {
    if (value === null || value === undefined || Number.isNaN(Number(value))) {
        return '–';
    }

    return `${Number(value).toFixed(1)}°`;
}

function range(day) {
    if (day.high_c === null || day.low_c === null) {
        return '–';
    }

    return `${Math.round(day.high_c)}° / ${Math.round(day.low_c)}°`;
}

async function load(announce = false) {
    const city = draft.value.trim().slice(0, limits.city);
    draft.value = city;

    if (!city) {
        error.value = 'Enter a city name.';

        if (announce) {
            push(error.value, 'error');
        }

        return;
    }

    loading.value = true;
    error.value = '';

    try {
        const { data } = await client.get('/integrations/weather', { params: { city } });
        report.value = data.data;
        draft.value = data.data.city || city;

        try {
            localStorage.setItem('iqt.weather.city', draft.value);
        } catch {
            // Storage can be blocked. The forecast still renders.
        }

        if (announce) {
            push(`Weather updated for ${draft.value}.`);
        }
    } catch (requestError) {
        error.value = requestError.response?.data?.message || 'Weather is unavailable right now.';
        push(error.value, 'error');
    } finally {
        loading.value = false;
    }
}

onMounted(() => load(false));
</script>

<template>
    <section class="card weather" aria-labelledby="weather-heading">
        <div class="card-head">
            <h2 id="weather-heading">Local weather</h2>
            <p>Open-Meteo, cached for 10 minutes</p>
        </div>

        <form class="stack" @submit.prevent="load(true)">
            <div class="field">
                <div class="field-label">
                    <label for="weather-city">City</label>
                    <span>{{ draft.length }}/{{ limits.city }}</span>
                </div>
                <div class="inline">
                    <input id="weather-city" v-model="draft" type="text" :maxlength="limits.city" autocomplete="off" placeholder="London">
                    <button class="btn btn-primary" type="submit" :disabled="loading">
                        {{ loading ? 'Looking up…' : 'Update' }}
                    </button>
                </div>
            </div>
            <p v-if="error" class="banner" role="alert">{{ error }}</p>
        </form>

        <div v-if="report" class="wx-body">
            <div class="wx-now">
                <p class="wx-temp">{{ formatTemp(report.temperature_c) }}</p>
                <div>
                    <p class="wx-cond">{{ report.condition }}</p>
                    <p class="wx-place">{{ place }}</p>
                </div>
            </div>

            <dl class="wx-facts">
                <div>
                    <dt>Humidity</dt>
                    <dd>{{ report.humidity ?? '–' }}<span v-if="report.humidity !== null">%</span></dd>
                </div>
                <div>
                    <dt>Wind</dt>
                    <dd>{{ report.wind_speed_kmh ?? '–' }}<span v-if="report.wind_speed_kmh !== null"> km/h</span></dd>
                </div>
            </dl>

            <ul v-if="report.daily?.length" class="wx-days">
                <li v-for="day in report.daily" :key="day.date">
                    <span>{{ weekday(day.date) }}</span>
                    <span>{{ day.condition }}</span>
                    <span>{{ range(day) }}</span>
                </li>
            </ul>

            <a class="attribution" :href="report.attribution_url" target="_blank" rel="noopener noreferrer">Data from Open-Meteo</a>
        </div>
    </section>
</template>

<template>
	<div class="p-6 bg-white rounded shadow">
		<h2 class="text-xl font-semibold mb-4">Rendelés statisztikák — Típusok szerint</h2>

		<div v-if="loading" class="text-center py-8">Loading statistics…</div>
		<div v-else-if="error" class="text-red-600">Error: {{ error }}</div>
		<div v-else>
			<!-- summary stats -->
			<div v-if="stats" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
				<div class="p-3 bg-gray-50 rounded">
					<div class="text-sm text-gray-500">Total orders</div>
					<div class="text-lg font-bold">{{ stats.total_orders }}</div>
				</div>
				<div class="p-3 bg-gray-50 rounded">
					<div class="text-sm text-gray-500">Total revenue</div>
					<div class="text-lg font-bold">{{ stats.total_revenue }} Ft</div>
				</div>
				<div class="p-3 bg-gray-50 rounded">
					<div class="text-sm text-gray-500">Pending</div>
					<div class="text-lg font-bold">{{ stats.pending_orders }}</div>
				</div>
				<div class="p-3 bg-gray-50 rounded">
					<div class="text-sm text-gray-500">Prepared</div>
					<div class="text-lg font-bold">{{ stats.prepared_orders }}</div>
				</div>
				<div class="p-3 bg-gray-50 rounded">
					<div class="text-sm text-gray-500">Delivery</div>
					<div class="text-lg font-bold">{{ stats.delivery_orders }}</div>
				</div>
				<div class="p-3 bg-gray-50 rounded">
					<div class="text-sm text-gray-500">Completed</div>
					<div class="text-lg font-bold">{{ stats.completed_orders }}</div>
				</div>
				<div class="p-3 bg-gray-50 rounded">
					<div class="text-sm text-gray-500">Cancelled</div>
					<div class="text-lg font-bold">{{ stats.cancelled_orders }}</div>
				</div>
			</div>

			<!-- most common items -->
			<div v-if="stats?.most_common_items?.length" class="mb-4">
				<h3 class="font-semibold mb-2">Top items</h3>
				<ul class="list-disc pl-5 text-sm text-gray-700">
					<li v-for="(it, idx) in stats.most_common_items" :key="idx">
						{{ it.menu?.name || it.menu_id }} — {{ it.total_quantity }}
					</li>
				</ul>
			</div>
			<div v-if="!chartData.length" class="text-gray-600">No data available.</div>

			<div v-else class="flex flex-col md:flex-row items-center gap-6">
				<svg :width="size" :height="size" :viewBox="`0 0 ${size} ${size}`">
					<g :transform="`translate(${size/2}, ${size/2})`">
												<g v-for="(slice, i) in slices" :key="i">
													<path
														:d="slice.path"
														:fill="slice.color"
														stroke="#fff"
														stroke-width="1"
													/>
												</g>
						<!-- center label -->
						<text v-if="total > 0" x="0" y="4" text-anchor="middle" class="text-sm" :style="{ fontSize: '14px', fill: '#333' }">{{ total }}</text>
					</g>
				</svg>

				<div>
					<ul class="space-y-2">
						<li v-for="(d, i) in chartData" :key="d.type" class="flex items-center gap-3">
							<span :style="{ backgroundColor: colors[i % colors.length] }" class="w-4 h-4 inline-block rounded-sm"></span>
							<div>
								<div class="font-medium">{{ d.type || 'Unknown' }}</div>
								<div class="text-sm text-gray-600">{{ d.total_quantity }} ({{ percent(d.total_quantity) }}%)</div>
							</div>
						</li>
					</ul>
				</div>
			</div>

			<div class="mt-4">
				<button @click="fetchStats" class="bg-blue-500 text-white px-3 py-1 rounded">Refresh</button>
			</div>
		</div>
	</div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useOrdersStore } from '@/stores/orders';

const ordersStore = useOrdersStore();

const loading = ref(false);
const error = ref(null);
const chartData = ref([]); // array of { type, total_quantity }
const stats = ref(null); // full statistics object from the API

const size = 220; // svg size
const radius = size / 2 - 4; // radius for slices

const colors = [
	'#4F46E5', // indigo
	'#06B6D4', // cyan
	'#F59E0B', // amber
	'#EF4444', // red
	'#10B981', // green
	'#8B5CF6', // purple
	'#F97316', // orange
	'#3B82F6', // blue
];

function percent(value) {
	const t = total.value || 0;
	if (!t) return 0;
	return Math.round((value / t) * 100);
}

const total = computed(() => chartData.value.reduce((s, it) => s + Number(it.total_quantity || 0), 0));

// convert data into SVG arc paths
const slices = computed(() => {
	const result = [];
	let startAngle = -90; // start at top
	for (let i = 0; i < chartData.value.length; i++) {
		const item = chartData.value[i];
		const value = Number(item.total_quantity || 0);
		const portion = total.value > 0 ? value / total.value : 0;
		const angle = portion * 360;
		const endAngle = startAngle + angle;
		const path = describeArcPath(0, 0, radius, startAngle, endAngle);
		result.push({ path, color: colors[i % colors.length], item });
		startAngle = endAngle;
	}
	return result;
});

function polarToCartesian(cx, cy, r, angleDeg) {
	const angleRad = ((angleDeg - 90) * Math.PI) / 180.0;
	return {
		x: cx + r * Math.cos(angleRad),
		y: cy + r * Math.sin(angleRad),
	};
}

function describeArcPath(cx, cy, r, startAngle, endAngle) {
	const start = polarToCartesian(cx, cy, r, endAngle);
	const end = polarToCartesian(cx, cy, r, startAngle);
	const largeArcFlag = endAngle - startAngle <= 180 ? '0' : '1';
	const d = [
		`M ${start.x} ${start.y}`,
		`A ${r} ${r} 0 ${largeArcFlag} 0 ${end.x} ${end.y}`,
		`L 0 0`,
		'Z',
	].join(' ');
	return d;
}

async function fetchStats() {
    loading.value = true;
    error.value = null;
	try {
		const body = await ordersStore.fetchOrderStatistics();
		// store.orderStatistics holds the full stats object; set local stats and the chart data
		stats.value = ordersStore.orderStatistics ?? body ?? null;
		chartData.value = stats.value?.type_of_menu_items ?? [];
    } catch (e) {
        error.value = e.message || String(e);
    } finally {
        loading.value = false;
    }
}

// fetch immediately during setup (on creation) instead of waiting for mounted
fetchStats();

</script>

<style scoped>
/* small responsive tweaks */
@media (min-width: 768px) {
	svg { width: 220px; height: 220px; }
}
</style>
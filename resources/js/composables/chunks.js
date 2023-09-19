import { ref } from 'vue';

export default function useStatus() {
    const statusColor = ref({
        new: 'blue',
        pending: 'grey',
        in_progress: 'orange',
        finished: 'green',
        failed: 'red',
    });
    return { statusColor };
}

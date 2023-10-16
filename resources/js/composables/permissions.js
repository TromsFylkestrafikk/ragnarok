import { reduce } from 'lodash';
import { computed } from 'vue';

export const permissionProps = {
    permissions: { type: Object, required: true },
};

export function usePermissions(props) {
    return {
        haveOperations: computed(() => reduce(props.permissions.operations, (carry, prop) => carry || prop, false)),
    };
}

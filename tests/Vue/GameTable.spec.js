import { mount } from '@vue/test-utils';
import { describe, it, expect, beforeEach, vi } from 'vitest';
import GameTable from '../../resources/js/components/GameTable.vue';
import { createTestingPinia } from '@pinia/testing';

// Mock Echo
vi.mock('../../resources/js/echo', () => ({
    default: {
        private: vi.fn(() => ({
            listen: vi.fn(),
            leave: vi.fn(),
        })),
    },
}));

describe('GameTable.vue', () => {
    let wrapper;
    
    const mockGame = {
        gameId: 1,
        initialPlayers: [
            { id: 1, position: 1, status: 'active', balance: 1000 },
            { id: 2, position: 2, status: 'active', balance: 1000 },
        ],
        initialBank: 0
    };

    beforeEach(() => {
        wrapper = mount(GameTable, {
            global: {
                plugins: [createTestingPinia()],
                mocks: {
                    $page: {
                        props: {
                            auth: {
                                user: { id: 1 }
                            }
                        }
                    }
                }
            },
            props: mockGame
        });
    });

    it('renders players correctly', () => {
        expect(wrapper.findAll('.player')).toHaveLength(2);
    });

    it('displays game bank', () => {
        expect(wrapper.find('.game-bank').text()).toContain('Банк: 0');
    });

    it('handles player actions', async () => {
        // Mock axios
        const axiosPost = vi.fn();
        wrapper.vm.$axios = { post: axiosPost };

        await wrapper.vm.handlePlayerAction({ type: 'raise', betAmount: 100 });
        
        expect(axiosPost).toHaveBeenCalledWith('/api/games/1/action', {
            player_id: 1,
            action: 'raise',
            bet_amount: 100
        });
    });
});
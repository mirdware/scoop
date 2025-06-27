import { Module } from 'scalar';
import messageComponent from '@/scoop/components/message';
import formComponent from '@/scoop/components/form';
import pageableComponent from '@/scoop/components/pageable';
import multiSelectComponent from '@/scoop/components/multi-select/multi-select';

export default new Module()
.compose('multi-select', multiSelectComponent)
.compose('#msg', messageComponent)
.compose('.scoop-form', formComponent)
.compose('.pageable', pageableComponent);

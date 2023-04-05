import { Module } from 'scalar';
import Messenger from './scoop/services/Messenger';
import Form from './scoop/services/Form';
import messageComponent from './scoop/components/message';
import formComponent from './scoop/components/form';
import pageableComponent from './scoop/components/pageable';
import Modal from './scoop/services/Modal';
import Overlay from './scoop/services/Overlay';

new Module(
    Messenger,
    Form,
    Modal,
    Overlay
).compose('#msg', messageComponent)
.compose('.scoop-form', formComponent)
.compose('.pageable', pageableComponent)
.execute();

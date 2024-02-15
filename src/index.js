// Import necessary modules
import { registerBlockType } from '@wordpress/blocks';

// Import Edit and Save components
import metadata from './block.json';
import Edit from './edit';
// import Save from './save'; // Using Edit component for Save as requested

// Register block type
registerBlockType( metadata.name, {
    edit: Edit, // Use the Edit component for editing
    save: () => null, // Empty save function
});
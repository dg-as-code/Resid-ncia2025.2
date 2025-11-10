# LLM Directory Documentation

This directory contains scripts and models related to Large Language Models (LLMs) used in the project. The purpose of this directory is to provide a structured approach to implementing and utilizing LLMs for various tasks.

## Directory Structure

- **scripts/**: Contains Python scripts for running the LLM and preparing input data.
  - `run_llm.py`: Script to execute the LLM model and generate predictions.
  - `prepare_inputs.py`: Script to format and prepare input data for the LLM.

- **models/**: Contains documentation and resources related to the LLM models used in this project.
  - `README.md`: Documentation detailing the architecture, training process, and usage of the models.

- **utils/**: Contains utility functions and classes that assist the main LLM scripts.
  - `llm_utils.py`: Helper functions for data preprocessing and model evaluation.

## Usage

1. **Install Dependencies**: Before running the scripts, ensure that all required Python packages are installed. You can find the list of dependencies in the `requirements.txt` file.

2. **Prepare Input Data**: Use the `prepare_inputs.py` script to format your input data correctly. This step is crucial for ensuring that the LLM can process the data effectively.

3. **Run the LLM**: Execute the `run_llm.py` script to run the model with the prepared input data. The script will handle the execution and output the results based on the model's predictions.

4. **Environment Variables**: Refer to the `.env.example` file for the necessary environment variables required for the scripts. Copy this file to `.env` and configure it according to your local setup.

By following these guidelines, you can effectively utilize the LLM capabilities within this project.